<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Block\Adminhtml\System\Config;

use AlpineCommerce\Turnstile\Block\Adminhtml\System\Config\NoAutofillField;
use Magento\Framework\Data\Form\Element\AbstractElement;
use PHPUnit\Framework\TestCase;

class NoAutofillFieldTest extends TestCase
{
    private function render(string $type, string $html): string
    {
        $block = $this->getMockBuilder(NoAutofillField::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $element = $this->createMock(AbstractElement::class);
        $element->method('getType')->willReturn($type);
        $element->method('getElementHtml')->willReturn($html);

        $method = new \ReflectionMethod(NoAutofillField::class, '_getElementHtml');

        return $method->invoke($block, $element);
    }

    public function testSecretFieldGetsNewPassword(): void
    {
        $html = $this->render('password', '<input id="secret" name="groups[general][fields][secret_key][value]" type="password" value="******"/>');

        $this->assertStringStartsWith('<input autocomplete="new-password" id="secret"', $html);
    }

    public function testTextFieldGetsOff(): void
    {
        $html = $this->render('text', '<input id="site_key" type="text" value=""/>');

        $this->assertStringStartsWith('<input autocomplete="off" id="site_key"', $html);
    }

    public function testExistingAutocompleteIsKept(): void
    {
        $html = $this->render('text', '<input autocomplete="username" id="site_key" type="text"/>');

        $this->assertSame('<input autocomplete="username" id="site_key" type="text"/>', $html);
    }

    public function testOnlyFirstInputIsChanged(): void
    {
        $html = $this->render('password', '<input id="a" type="password"/><input id="b" type="hidden"/>');

        $this->assertSame('<input autocomplete="new-password" id="a" type="password"/><input id="b" type="hidden"/>', $html);
    }
}
