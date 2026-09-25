<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration\Controller;

use AlpineCommerce\Turnstile\Test\Integration\TurnstileIntegrationTrait;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Guard for R4: the rendered page shows the widget inside the protected form, under Luma (container
 * declared by a Magento module) and under Hyvä (declared by the theme, merged after the modules).
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class WidgetRenderingTest extends AbstractController
{
    use TurnstileIntegrationTrait;

    private const ALL_PRESETS = [
        'contact' => true,
        'customer_create' => true,
        'customer_login' => true,
        'customer_forgot_password' => true,
        'newsletter' => true,
        'product_review' => true,
        'sendfriend' => true,
        'wishlist_share' => true,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableTurnstile(self::ALL_PRESETS);
    }

    protected function tearDown(): void
    {
        $this->resetTurnstile();
        parent::tearDown();
    }

    /**
     * @dataProvider protectedPages
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store sendfriend/email/enabled 1
     * @magentoConfigFixture current_store sendfriend/email/allow_guest 1
     */
    public function testWidgetIsRenderedInsideTheForm(string $theme, string $uri, string $formId): void
    {
        $this->useTheme($theme);

        $this->dispatch($uri);

        $this->assertRenderedWith($theme);
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode(), $uri);
        $this->assertGreaterThan(0, $this->widgetsInForms($formId), sprintf('%s: no %s widget inside a form on %s', $theme, $formId, $uri));
    }

    /**
     * U-B3: the Hyvä review form posts through GraphQL; the theme does not render the review container,
     * so no widget that could never be validated is shown.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testHyvaProductPageShowsNoReviewWidget(): void
    {
        $this->useTheme('AlpineCommerce/hyva');

        $this->dispatch('catalog/product/view/id/1');

        $this->assertRenderedWith('AlpineCommerce/hyva');
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertSame(0, $this->widgetsInForms('product_review') + $this->widgets('product_review'));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function protectedPages(): array
    {
        $pages = [];
        foreach (['luma' => 'Magento/luma', 'hyva' => 'AlpineCommerce/hyva'] as $label => $theme) {
            $pages[$label . ' contact'] = [$theme, 'contact/index/index', 'contact'];
            $pages[$label . ' create account'] = [$theme, 'customer/account/create', 'customer_create'];
            $pages[$label . ' login'] = [$theme, 'customer/account/login', 'customer_login'];
            $pages[$label . ' forgot password'] = [$theme, 'customer/account/forgotpassword', 'customer_forgot_password'];
            $pages[$label . ' email to a friend'] = [$theme, 'sendfriend/product/send/id/1', 'sendfriend'];
        }
        $pages['luma product review'] = ['Magento/luma', 'catalog/product/view/id/1', 'product_review'];

        return $pages;
    }

    private function useTheme(string $themePath): void
    {
        $theme = $this->_objectManager->get(ThemeProviderInterface::class)->getThemeByFullPath('frontend/' . $themePath);
        $this->assertNotEmpty($theme->getId(), $themePath . ' is not registered');
        // The area initialises its design once and keeps the shared design object: set the theme on that
        // object (as Magento's own tests do) and in the configuration, in case the area loads later.
        $this->_objectManager->get(MutableScopeConfigInterface::class)
            ->setValue(DesignInterface::XML_PATH_THEME_ID, $theme->getId(), ScopeInterface::SCOPE_STORE, 'default');
        $this->_objectManager->get(DesignInterface::class)->setDesignTheme($themePath, 'frontend');
    }

    /**
     * Without this check a page silently rendered with another theme would give a false result.
     */
    private function assertRenderedWith(string $themePath): void
    {
        $this->assertSame(
            $themePath,
            $this->_objectManager->get(DesignInterface::class)->getDesignTheme()->getThemePath(),
            'The page was not rendered with the expected theme.'
        );
    }

    private function widgetsInForms(string $formId): int
    {
        return $this->countNodes(sprintf(
            "//form//div[contains(concat(' ', normalize-space(@class), ' '), ' cf-turnstile ') and @data-action='%s']",
            $formId
        ));
    }

    private function widgets(string $formId): int
    {
        return $this->countNodes(sprintf("//div[@data-action='%s']", $formId));
    }

    private function countNodes(string $xpath): int
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML((string) $this->getResponse()->getBody());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return (new \DOMXPath($document))->query($xpath)->length;
    }
}
