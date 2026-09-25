<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration\Controller;

use AlpineCommerce\Turnstile\Model\FormDefinition;
use AlpineCommerce\Turnstile\Model\FormRegistry;
use AlpineCommerce\Turnstile\Test\Integration\TurnstileIntegrationTrait;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\UrlRewrite\Model\UrlRewrite;

/**
 * End-to-end protection of a real Magento form (Contact Us) through the generic observer: the controller
 * really runs or not (e-mail sent or not), with Cloudflare replaced by FakeSiteVerifyClient.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class FormProtectionTest extends AbstractController
{
    use TurnstileIntegrationTrait;

    private const REJECTED = 'Please complete the security check.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableTurnstile(['contact' => true]);
    }

    protected function tearDown(): void
    {
        $this->resetTurnstile();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function postContact(array $extra = [], bool $withFormKey = true): void
    {
        $post = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'telephone' => '',
            'comment' => 'Hello from the Turnstile integration test',
            'hideit' => '',
        ];
        // AbstractController::dispatch() adds a valid form key to any POST without one; an explicit null
        // is how Magento's own tests send a request without form key (LoginPostTest).
        $post['form_key'] = $withFormKey ? $this->_objectManager->get(FormKey::class)->getFormKey() : null;
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue(array_merge($post, $extra));
    }

    private function asAjax(): void
    {
        $this->getRequest()->getHeaders()->addHeaderLine('X-Requested-With', 'XMLHttpRequest');
    }

    private function mailSent(): bool
    {
        return $this->_objectManager->get(TransportBuilderMock::class)->getSentMessage() !== null;
    }

    public function testValidTokenWithFormKeyReachesTheController(): void
    {
        $this->postContact(['cf-turnstile-response' => 'valid-token']);

        $this->dispatch('contact/index/post');

        $this->assertTrue($this->mailSent());
        $this->assertSame(1, $this->siteVerify->calls);
    }

    public function testMissingTokenIsRejectedAndTheControllerDoesNotRun(): void
    {
        $this->postContact();

        $this->dispatch('contact/index/post');

        $this->assertFalse($this->mailSent());
        $this->assertSame(0, $this->siteVerify->calls);
        $this->assertRedirect($this->stringContains('contact/index'));
        $this->assertSessionMessages($this->equalTo([self::REJECTED]), MessageInterface::TYPE_ERROR);
    }

    /**
     * S14 / S15: without form key, Magento's CSRF check rejects a classic POST before Turnstile runs.
     */
    public function testNonAjaxPostWithoutFormKeyIsRejectedByCsrfBeforeTurnstile(): void
    {
        $this->postContact(['cf-turnstile-response' => 'valid-token'], false);

        $this->dispatch('contact/index/post');

        $this->assertFalse($this->mailSent());
        $this->assertSame(0, $this->siteVerify->calls);
    }

    /**
     * S15b: Magento skips the form key for AJAX; Turnstile is then the only barrier.
     */
    public function testAjaxPostWithoutFormKeyAndTokenIsRejectedByTurnstileWithJson400(): void
    {
        $this->asAjax();
        $this->postContact([], false);

        $this->dispatch('contact/index/post');

        $this->assertFalse($this->mailSent());
        $this->assertSame(400, $this->getResponse()->getHttpResponseCode());
        $body = json_decode((string) $this->getResponse()->getBody(), true);
        $this->assertSame(['success' => false, 'error' => 'turnstile', 'code' => 'missing', 'message' => self::REJECTED], $body);
    }

    /**
     * S15b: the same AJAX request with a valid token reaches the controller, which proves the rejection
     * above comes from Turnstile and not from the CSRF check.
     */
    public function testAjaxPostWithoutFormKeyButValidTokenReachesTheController(): void
    {
        $this->asAjax();
        $this->postContact(['cf-turnstile-response' => 'valid-token'], false);

        $this->dispatch('contact/index/post');

        $this->assertTrue($this->mailSent());
        $this->assertSame(1, $this->siteVerify->calls);
    }

    public function testQueryTokenIsIgnored(): void
    {
        $this->postContact();
        $this->getRequest()->setQueryValue(['cf-turnstile-response' => 'valid-token']);

        $this->dispatch('contact/index/post');

        $this->assertFalse($this->mailSent());
        $this->assertSame(0, $this->siteVerify->calls);
    }

    public function testHeaderTokenIsAccepted(): void
    {
        $this->postContact();
        $this->getRequest()->getHeaders()->addHeaderLine('X-Turnstile-Token', 'valid-token');

        $this->dispatch('contact/index/post');

        $this->assertTrue($this->mailSent());
    }

    public function testRouteMatchIsCaseInsensitive(): void
    {
        $this->postContact();

        $this->dispatch('contact/INDEX/POST');

        $this->assertFalse($this->mailSent());
        $this->assertSessionMessages($this->equalTo([self::REJECTED]), MessageInterface::TYPE_ERROR);
    }

    public function testUrlRewriteToProtectedActionIsStillProtected(): void
    {
        $this->createContactPostRewrite();
        $this->postContact();

        $this->dispatch('turnstile-test-contact-post');

        $this->assertFalse($this->mailSent());
        $this->assertSessionMessages($this->equalTo([self::REJECTED]), MessageInterface::TYPE_ERROR);
    }

    /**
     * Control for the rewrite test: the rewrite really leads to the contact controller.
     */
    public function testUrlRewriteReachesTheContactControllerWithAValidToken(): void
    {
        $this->createContactPostRewrite();
        $this->postContact(['cf-turnstile-response' => 'valid-token']);

        $this->dispatch('turnstile-test-contact-post');

        $this->assertTrue($this->mailSent());
    }

    /**
     * K1: the rejected input is kept in the DataPersistor the contact form reads, without token or form key.
     */
    public function testContactRejectKeepsInput(): void
    {
        $this->postContact(['cf-turnstile-response' => '   ']);

        $this->dispatch('contact/index/post');

        $kept = $this->_objectManager->get(DataPersistorInterface::class)->get('contact_us');
        $this->assertSame('Jane Doe', $kept['name'] ?? null);
        $this->assertSame('jane@example.com', $kept['email'] ?? null);
        $this->assertSame('Hello from the Turnstile integration test', $kept['comment'] ?? null);
        $this->assertArrayNotHasKey('form_key', $kept);
        $this->assertArrayNotHasKey('cf-turnstile-response', $kept);
    }

    /**
     * S25 / E15: a controller that declares no HTTP method interface (AlpineCommerce_Rma) is protected
     * against GET as soon as its route is registered.
     */
    public function testGetOnRouteWithoutMethodInterfaceIsRejected(): void
    {
        if (!$this->_objectManager->get(ModuleManager::class)->isEnabled('AlpineCommerce_Rma')) {
            $this->markTestSkipped('AlpineCommerce_Rma is not enabled in this installation.');
        }
        $this->registerForm('rma_request', ['rma_index_request']);
        $this->enableTurnstile(['contact' => true, 'rma_request' => true]);

        $this->dispatch('rma/index/request?order_id=1');

        $this->assertSame(0, $this->siteVerify->calls);
        $this->assertSessionMessages(
            $this->equalTo(['The security check failed. Please try again.']),
            MessageInterface::TYPE_ERROR
        );
    }

    private function createContactPostRewrite(): void
    {
        $this->_objectManager->create(UrlRewrite::class)
            ->setStoreId(1)
            ->setEntityType('custom')
            ->setEntityId(0)
            ->setRequestPath('turnstile-test-contact-post')
            ->setTargetPath('contact/index/post')
            ->setRedirectType(0)
            ->save();
    }

    /**
     * Registers an extra form the way a module does in its di.xml.
     *
     * @param string[] $actions
     */
    private function registerForm(string $formId, array $actions): void
    {
        $virtualType = 'AlpineCommerceTurnstileIntegrationForm_' . $formId;
        $arguments = $this->_objectManager->get(ObjectManagerConfig::class)->getArguments(FormRegistry::class);
        $arguments['forms'][$formId] = ['instance' => $virtualType];
        $this->_objectManager->configure([
            $virtualType => [
                'type' => FormDefinition::class,
                'arguments' => ['id' => $formId, 'label' => $formId, 'actions' => $actions],
            ],
            FormRegistry::class => ['arguments' => $arguments],
        ]);
        $this->_objectManager->removeSharedInstance(FormRegistry::class);
    }
}
