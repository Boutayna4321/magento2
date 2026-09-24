<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class ConfigTest extends TestCase
{
    private const ENABLED = [
        Config::XML_PATH_ENABLED => '1',
        Config::XML_PATH_FORM_PREFIX . 'contact' => '1',
        Config::XML_PATH_SITE_KEY => 'site-key',
        Config::XML_PATH_SECRET_KEY => 'encrypted-secret',
    ];

    private AbstractLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };
    }

    private function config(array $values): Config
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path) => $values[$path] ?? null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn (string $path) => (bool) ($values[$path] ?? false)
        );
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->method('decrypt')->willReturnCallback(
            static fn (string $value) => $value === 'encrypted-secret' ? 'plain-secret' : ''
        );

        return new Config($scopeConfig, $encryptor, $this->logger);
    }

    public function testEnabledWhenAllConditionsHold(): void
    {
        $this->assertTrue($this->config(self::ENABLED)->isEnabledFor('contact', 6));
    }

    public function testDisabledGlobally(): void
    {
        $values = [Config::XML_PATH_ENABLED => '0'] + self::ENABLED;
        $this->assertFalse($this->config($values)->isEnabledFor('contact', 6));
    }

    public function testDisabledForForm(): void
    {
        $values = [Config::XML_PATH_FORM_PREFIX . 'contact' => '0'] + self::ENABLED;
        $this->assertFalse($this->config($values)->isEnabledFor('contact', 6));
    }

    public function testUnknownFormIsDisabled(): void
    {
        $this->assertFalse($this->config(self::ENABLED)->isEnabledFor('newsletter', 6));
    }

    public function testMissingSecretDisablesAndLogsWarning(): void
    {
        $values = [Config::XML_PATH_SECRET_KEY => ''] + self::ENABLED;
        $this->assertFalse($this->config($values)->isEnabledFor('contact', 6));
        $this->assertSame('warning', $this->logger->records[0]['level']);
        $this->assertSame(['form_id' => 'contact', 'store_id' => 6], $this->logger->records[0]['context']);
    }

    public function testWhitespaceKeysCountAsMissing(): void
    {
        $values = [Config::XML_PATH_SITE_KEY => "   \t"] + self::ENABLED;
        $this->assertFalse($this->config($values)->isEnabledFor('contact', 6));
    }

    public function testSecretIsDecrypted(): void
    {
        $this->assertSame('plain-secret', $this->config(self::ENABLED)->getSecretKey(6));
    }

    public function testTimeoutDefaultsWhenOutOfRange(): void
    {
        $this->assertSame(5, $this->config([Config::XML_PATH_TIMEOUT => '0'])->getTimeout());
        $this->assertSame(5, $this->config([Config::XML_PATH_TIMEOUT => '31'])->getTimeout());
        $this->assertSame(10, $this->config([Config::XML_PATH_TIMEOUT => '10'])->getTimeout());
    }

    public function testFailureModeDefaultsToClosed(): void
    {
        $this->assertSame('closed', $this->config([])->getFailureMode());
        $this->assertSame('closed', $this->config([Config::XML_PATH_FAILURE_MODE => 'bogus'])->getFailureMode());
        $this->assertSame('open', $this->config([Config::XML_PATH_FAILURE_MODE => 'open'])->getFailureMode());
    }

    public function testThemeFallsBackToAuto(): void
    {
        $this->assertSame('auto', $this->config([Config::XML_PATH_THEME => 'neon'])->getTheme());
        $this->assertSame('dark', $this->config([Config::XML_PATH_THEME => 'dark'])->getTheme());
    }
}
