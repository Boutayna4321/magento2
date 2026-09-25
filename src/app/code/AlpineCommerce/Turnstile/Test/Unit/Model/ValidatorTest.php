<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Exception\SiteVerifyUnavailableException;
use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\Model\SiteVerifyClient;
use AlpineCommerce\Turnstile\Model\ValidationResult;
use AlpineCommerce\Turnstile\Model\Validator;
use Magento\Framework\App\State;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class ValidatorTest extends TestCase
{
    private const TOKEN = 'XXXX.DUMMY.TOKEN.XXXX';
    private const SECRET = '1x0000000000000000000000000000000AA';

    private SiteVerifyClient&MockObject $client;
    private Config&MockObject $config;
    private State&MockObject $appState;
    private AbstractLogger $logger;
    private Validator $validator;

    protected function setUp(): void
    {
        $this->client = $this->createMock(SiteVerifyClient::class);
        $this->config = $this->createMock(Config::class);
        $this->config->method('getSecretKey')->willReturn(self::SECRET);
        $this->config->method('getTimeout')->willReturn(5);
        $this->logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };
        $this->appState = $this->createMock(State::class);
        $this->validator = new Validator($this->client, $this->config, $this->logger, $this->appState);
    }

    private function failureMode(string $mode): void
    {
        $this->config->method('getFailureMode')->willReturn($mode);
    }

    public function testSuccessWithMatchingAction(): void
    {
        $this->client->expects($this->once())->method('verify')
            ->with(self::SECRET, self::TOKEN, '203.0.113.5', 5)
            ->willReturn(['success' => true, 'action' => 'contact']);

        $this->assertTrue($this->validator->validate(self::TOKEN, '203.0.113.5', 'contact', 6)->isValid());
    }

    public function testEmptyTokenIsUserErrorWithoutHttpCall(): void
    {
        $this->client->expects($this->never())->method('verify');

        $result = $this->validator->validate('', null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_USER, $result->getErrorType());
        $this->assertSame(['missing-input-response'], $result->getErrorCodes());
    }

    public function testOversizedTokenIsUserErrorWithoutHttpCall(): void
    {
        $this->client->expects($this->never())->method('verify');

        $result = $this->validator->validate(str_repeat('a', 2049), null, 'contact', 6);
        $this->assertSame(['token-too-long'], $result->getErrorCodes());
    }

    public function testInvalidTokenIsUserError(): void
    {
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['invalid-input-response']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertFalse($result->isValid());
        $this->assertSame(ValidationResult::ERROR_USER, $result->getErrorType());
    }

    public function testDuplicateTokenIsUserError(): void
    {
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['timeout-or-duplicate']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_USER, $result->getErrorType());
        $this->assertSame(['timeout-or-duplicate'], $result->getErrorCodes());
    }

    public function testActionMismatchIsRejected(): void
    {
        $this->client->method('verify')->willReturn(['success' => true, 'action' => 'newsletter']);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(['action-mismatch'], $result->getErrorCodes());
        $this->assertSame('warning', $this->logger->records[0]['level']);
    }

    public function testSuccessWithoutActionIsRejected(): void
    {
        $this->client->method('verify')->willReturn(['success' => true]);

        $this->assertFalse($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
    }

    public function testTestingKeyResponseWithoutActionIsAccepted(): void
    {
        // Real answer of Siteverify for the official test secret: no "action" field.
        $this->client->method('verify')->willReturn([
            'success' => true,
            'error-codes' => [],
            'hostname' => 'example.com',
            'metadata' => ['result_with_testing_key' => true],
        ]);

        $this->assertTrue($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
    }

    public function testTestingKeyResponseIsLoggedAsWarningOutsideProduction(): void
    {
        $this->appState->method('getMode')->willReturn(State::MODE_DEVELOPER);
        $this->client->method('verify')->willReturn([
            'success' => true,
            'metadata' => ['result_with_testing_key' => true],
        ]);

        $this->assertTrue($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
        $this->assertSame('warning', $this->logger->records[0]['level']);
        $this->assertSame(['form_id' => 'contact', 'store_id' => 6], $this->logger->records[0]['context']);
    }

    public function testTestingKeyResponseIsRejectedInProduction(): void
    {
        $this->appState->method('getMode')->willReturn(State::MODE_PRODUCTION);
        $this->client->method('verify')->willReturn([
            'success' => true,
            'metadata' => ['result_with_testing_key' => true],
        ]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertFalse($result->isValid());
        $this->assertSame(ValidationResult::ERROR_CONFIG, $result->getErrorType());
        $this->assertSame(['testing-key-in-production'], $result->getErrorCodes());
        $this->assertSame('critical', $this->logger->records[0]['level']);
    }

    public function testTestingKeyResponseWithOtherActionIsRejected(): void
    {
        $this->client->method('verify')->willReturn([
            'success' => true,
            'action' => 'newsletter',
            'metadata' => ['result_with_testing_key' => true],
        ]);

        $this->assertSame(['action-mismatch'], $this->validator->validate(self::TOKEN, null, 'contact', 6)->getErrorCodes());
    }

    public function testNonBooleanTestingKeyFlagIsIgnored(): void
    {
        $this->client->method('verify')->willReturn([
            'success' => true,
            'metadata' => ['result_with_testing_key' => 'true'],
        ]);

        $this->assertFalse($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
    }

    public function testNonBooleanSuccessIsRejected(): void
    {
        $this->client->method('verify')->willReturn(['success' => 'true', 'action' => 'contact']);

        $this->assertFalse($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
    }

    public function testInvalidSecretIsConfigErrorAndCritical(): void
    {
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['invalid-input-secret']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_CONFIG, $result->getErrorType());
        $this->assertSame('critical', $this->logger->records[0]['level']);
    }

    public function testInvalidSecretIsRejectedEvenInOpenMode(): void
    {
        $this->failureMode('open');
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['invalid-input-secret']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertFalse($result->isValid());
        $this->assertSame(ValidationResult::ERROR_CONFIG, $result->getErrorType());
    }

    public function testInternalErrorClosedIsUnavailable(): void
    {
        $this->failureMode('closed');
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['internal-error']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_UNAVAILABLE, $result->getErrorType());
        $this->assertSame('error', $this->logger->records[0]['level']);
    }

    public function testTransportFailureClosedIsUnavailable(): void
    {
        $this->failureMode('closed');
        $this->client->method('verify')->willThrowException(new SiteVerifyUnavailableException('HTTP 503', 503));

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_UNAVAILABLE, $result->getErrorType());
        $this->assertSame(503, $this->logger->records[0]['context']['http_status']);
    }

    public function testTransportFailureOpenIsAcceptedWithWarning(): void
    {
        $this->failureMode('open');
        $this->client->method('verify')->willThrowException(new SiteVerifyUnavailableException('timeout'));

        $this->assertTrue($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
        $this->assertSame('warning', $this->logger->records[0]['level']);
    }

    public function testFailureModeIsReadForTheValidatedForm(): void
    {
        $this->config->expects($this->once())->method('getFailureMode')
            ->with(6, 'customer_login')
            ->willReturn('open');
        $this->client->method('verify')->willThrowException(new SiteVerifyUnavailableException('timeout'));

        $this->assertTrue($this->validator->validate(self::TOKEN, null, 'customer_login', 6)->isValid());
    }

    public function testLogsNeverContainTokenOrSecret(): void
    {
        $this->failureMode('closed');
        $this->client->method('verify')->willThrowException(new SiteVerifyUnavailableException('timeout'));
        $this->validator->validate(self::TOKEN, '203.0.113.5', 'contact', 6);

        $dump = json_encode($this->logger->records);
        $this->assertNotEmpty($this->logger->records);
        $this->assertStringNotContainsString(self::TOKEN, $dump);
        $this->assertStringNotContainsString(self::SECRET, $dump);
    }
}
