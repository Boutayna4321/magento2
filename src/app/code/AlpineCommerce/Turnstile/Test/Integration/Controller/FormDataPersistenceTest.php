<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration\Controller;

use AlpineCommerce\Turnstile\Test\Integration\TurnstileIntegrationTrait;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * K2–K7 (decision D7): after a Turnstile rejection, the input is kept in the session the native Magento
 * form reads back, by the setter Magento itself uses, and is read here with Magento's own getter.
 * Passwords, the Turnstile token and the form key are never kept.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class FormDataPersistenceTest extends AbstractController
{
    use TurnstileIntegrationTrait;

    private const PASSWORD = 'Secret-Pass-123!';
    private const TOKEN = 'rejected-token-value';

    protected function tearDown(): void
    {
        $this->resetTurnstile();
        parent::tearDown();
    }

    /**
     * Sends a POST that Turnstile rejects (the fake Cloudflare answer fails), with a valid form key.
     *
     * @param array<string, mixed> $post
     */
    private function rejectedPost(string $formId, string $uri, array $post): void
    {
        $this->enableTurnstile([$formId => true]);
        $this->siteVerify->answer = ['success' => false, 'error-codes' => ['invalid-input-response']];
        $post['cf-turnstile-response'] = self::TOKEN;
        $post['form_key'] = $this->_objectManager->get(FormKey::class)->getFormKey();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST)->setPostValue($post);

        $this->dispatch($uri);

        $this->assertSame(1, $this->siteVerify->calls, 'The request must reach Turnstile and be rejected.');
    }

    private function assertNothingSensitive(mixed $kept): void
    {
        $dump = (string) json_encode($kept);
        $this->assertStringNotContainsString(self::PASSWORD, $dump);
        $this->assertStringNotContainsString(self::TOKEN, $dump);
        $this->assertStringNotContainsString('form_key', $dump);
    }

    public function testCustomerCreateRejectKeepsInputWithoutPasswords(): void
    {
        $this->rejectedPost('customer_create', 'customer/account/createpost', [
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => 'jane.create@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $kept = $this->_objectManager->get(CustomerSession::class)->getCustomerFormData(true);
        $this->assertSame('Jane', $kept['firstname'] ?? null);
        $this->assertSame('jane.create@example.com', $kept['email'] ?? null);
        $this->assertArrayNotHasKey('password', $kept);
        $this->assertArrayNotHasKey('password_confirmation', $kept);
        $this->assertNothingSensitive($kept);
    }

    public function testLoginRejectKeepsUsernameOnly(): void
    {
        $this->rejectedPost('customer_login', 'customer/account/loginPost', [
            'login' => ['username' => 'jane.login@example.com', 'password' => self::PASSWORD],
        ]);

        $session = $this->_objectManager->get(CustomerSession::class);
        $this->assertSame('jane.login@example.com', $session->getUsername());
        $this->assertFalse($session->isLoggedIn());
        $this->assertNothingSensitive($session->getData());
    }

    public function testForgotPasswordRejectKeepsEmail(): void
    {
        $this->rejectedPost('customer_forgot_password', 'customer/account/forgotpasswordpost', [
            'email' => 'jane.forgot@example.com',
        ]);

        $this->assertSame(
            'jane.forgot@example.com',
            $this->_objectManager->get(CustomerSession::class)->getForgottenEmail()
        );
    }

    public function testSendFriendRejectKeepsInput(): void
    {
        $this->rejectedPost('sendfriend', 'sendfriend/product/sendmail/id/1', [
            'sender' => ['name' => 'Jane', 'email' => 'jane.friend@example.com', 'message' => 'Look at this'],
            'recipients' => ['name' => ['Joe'], 'email' => ['joe@example.com']],
        ]);

        $kept = $this->_objectManager->get(CatalogSession::class)->getSendfriendFormData();
        $this->assertSame('Look at this', $kept['sender']['message'] ?? null);
        $this->assertSame(['joe@example.com'], $kept['recipients']['email'] ?? null);
        $this->assertNothingSensitive($kept);
    }

    public function testWishlistShareRejectKeepsInput(): void
    {
        $this->rejectedPost('wishlist_share', 'wishlist/index/send', [
            'emails' => 'joe@example.com',
            'message' => 'My wishlist',
        ]);

        // Magento\Wishlist\Model\Session is a virtualType of Generic: fetched by its own name, like the controller.
        $kept = $this->_objectManager->get('Magento\Wishlist\Model\Session')->getData('sharing_form', true);
        $this->assertSame('joe@example.com', $kept['emails'] ?? null);
        $this->assertSame('My wishlist', $kept['message'] ?? null);
        $this->assertNothingSensitive($kept);
    }

    public function testReviewRejectKeepsInput(): void
    {
        $this->rejectedPost('product_review', 'review/product/post/id/1', [
            'nickname' => 'Jane',
            'title' => 'Great',
            'detail' => 'Really great product',
        ]);

        // Magento\Review\Model\Session is a virtualType of Generic, read by Review\CustomerData\Review.
        $kept = $this->_objectManager->get('Magento\Review\Model\Session')->getFormData(true);
        $this->assertSame('Jane', $kept['nickname'] ?? null);
        $this->assertSame('Really great product', $kept['detail'] ?? null);
        $this->assertNothingSensitive($kept);
    }
}
