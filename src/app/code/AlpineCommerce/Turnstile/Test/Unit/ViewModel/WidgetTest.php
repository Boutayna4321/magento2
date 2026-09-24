<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\ViewModel;

use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\ViewModel\Widget;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WidgetTest extends TestCase
{
    private Config&MockObject $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
    }

    private function widget(string $locale): Widget
    {
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->method('getLocale')->willReturn($locale);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(6);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Widget($this->config, $resolver, $storeManager);
    }

    public static function locales(): array
    {
        return [
            'french' => ['fr_FR', 'fr'],
            'swiss german' => ['de_CH', 'de'],
            'portuguese' => ['pt_PT', 'pt'],
            'swedish' => ['sv_SE', 'sv'],
            'british' => ['en_GB', 'en'],
            'unsupported' => ['xx_YY', 'auto'],
            'empty' => ['', 'auto'],
        ];
    }

    #[DataProvider('locales')]
    public function testLanguageFromStoreLocale(string $locale, string $expected): void
    {
        $this->assertSame($expected, $this->widget($locale)->getLanguage());
    }

    public function testDelegatesToConfigWithCurrentStore(): void
    {
        $this->config->expects($this->once())->method('isEnabledFor')->with('contact', 6)->willReturn(true);
        $this->config->method('getSiteKey')->with(6)->willReturn('site-key');
        $this->config->method('getTheme')->with(6)->willReturn('dark');
        $widget = $this->widget('fr_FR');

        $this->assertTrue($widget->isEnabled('contact'));
        $this->assertSame('site-key', $widget->getSiteKey());
        $this->assertSame('dark', $widget->getTheme());
        $this->assertSame('https://challenges.cloudflare.com/turnstile/v0/api.js', $widget->getScriptUrl());
    }
}
