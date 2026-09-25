<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use AlpineCommerce\Turnstile\Model\FormDefinition;
use AlpineCommerce\Turnstile\Model\FormRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * The eight Magento presets are declared in di.xml, with their default switches in config.xml.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class FormRegistryPresetsTest extends TestCase
{
    /**
     * id => [action, required module, redirect path, redirect params, reCAPTCHA config path]
     */
    private const PRESETS = [
        'contact' => ['contact_index_post', 'Magento_Contact', 'contact/index', [], 'recaptcha_frontend/type_for/contact'],
        'customer_create' => [
            'customer_account_createpost',
            'Magento_Customer',
            'customer/account/create',
            ['_secure' => true],
            'recaptcha_frontend/type_for/customer_create',
        ],
        'customer_login' => [
            'customer_account_loginpost',
            'Magento_Customer',
            'customer/account/login',
            ['_secure' => true],
            'recaptcha_frontend/type_for/customer_login',
        ],
        'customer_forgot_password' => [
            'customer_account_forgotpasswordpost',
            'Magento_Customer',
            'customer/account/forgotpassword',
            ['_secure' => true],
            'recaptcha_frontend/type_for/customer_forgot_password',
        ],
        'newsletter' => ['newsletter_subscriber_new', 'Magento_Newsletter', null, [], 'recaptcha_frontend/type_for/newsletter'],
        'product_review' => ['review_product_post', 'Magento_Review', null, [], 'recaptcha_frontend/type_for/product_review'],
        'sendfriend' => ['sendfriend_product_sendmail', 'Magento_SendFriend', null, [], 'recaptcha_frontend/type_for/sendfriend'],
        'wishlist_share' => [
            'wishlist_index_send',
            'Magento_Wishlist',
            'wishlist/index/share',
            [],
            'recaptcha_frontend/type_for/wishlist',
        ],
    ];

    public function testEightPresetsAreRegisteredInDisplayOrder(): void
    {
        $registry = Bootstrap::getObjectManager()->get(FormRegistry::class);

        $this->assertSame(array_keys(self::PRESETS), array_keys($registry->getAll()));
    }

    public function testEachPresetDeclaresItsRouteModuleRedirectAndRecaptchaPath(): void
    {
        $registry = Bootstrap::getObjectManager()->get(FormRegistry::class);

        foreach (self::PRESETS as $id => [$action, $module, $redirectPath, $redirectParams, $recaptchaPath]) {
            $form = $registry->findByAction($action);
            $this->assertNotNull($form, $id);
            $this->assertSame($id, $form->getId());
            $this->assertSame([$action], $form->getActions(), $id);
            $this->assertSame($module, $form->getRequiredModule(), $id);
            $this->assertSame(FormDefinitionInterface::FAILURE_RESPONSE_AUTO, $form->getFailureResponse(), $id);
            $this->assertSame($redirectPath, $form->getRedirectPath(), $id);
            $this->assertSame($redirectParams, $form->getRedirectParams(), $id);
            $this->assertSame($recaptchaPath, $form->getRecaptchaConfigPath(), $id);
            $this->assertNotSame('', $form->getLabel(), $id);
        }
    }

    public function testDefaultSwitchesProtectEveryPresetExceptNewsletterAndWishlist(): void
    {
        $scopeConfig = Bootstrap::getObjectManager()->get(ScopeConfigInterface::class);
        $expected = [
            'contact' => '1',
            'customer_create' => '1',
            'customer_login' => '1',
            'customer_forgot_password' => '1',
            'newsletter' => '0',
            'product_review' => '1',
            'sendfriend' => '1',
            'wishlist_share' => '0',
        ];

        $actual = [];
        foreach (array_keys($expected) as $id) {
            $actual[$id] = (string) $scopeConfig->getValue('alpinecommerce_turnstile/forms/' . $id);
        }

        $this->assertSame($expected, $actual);
        $this->assertSame('0', (string) $scopeConfig->getValue('alpinecommerce_turnstile/general/enabled'));
    }

    /**
     * A module registers its own form by adding an item to FormRegistry's "forms" argument in its di.xml.
     * This test applies the same DI configuration and checks the presets stay registered beside it.
     */
    public function testFormDeclaredThroughDiIsRegisteredBesideThePresets(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $virtualType = 'AlpineCommerceTurnstileIntegrationBlogCommentForm';
        $arguments = $objectManager->get(ObjectManagerConfig::class)->getArguments(FormRegistry::class);
        $arguments['forms']['blog_comment'] = ['instance' => $virtualType];
        $objectManager->configure([
            $virtualType => [
                'type' => FormDefinition::class,
                'arguments' => [
                    'id' => 'blog_comment',
                    'label' => 'Blog Comment',
                    'actions' => ['blog_comment_post'],
                ],
            ],
            FormRegistry::class => ['arguments' => $arguments],
        ]);
        $registry = $objectManager->create(FormRegistry::class);

        $form = $registry->findByAction('blog_comment_post');
        $this->assertNotNull($form);
        $this->assertSame('blog_comment', $form->getId());
        $this->assertCount(count(self::PRESETS) + 1, $registry->getAll());
    }
}
