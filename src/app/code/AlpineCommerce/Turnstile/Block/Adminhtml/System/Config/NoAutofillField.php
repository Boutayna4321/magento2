<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Stops browsers from autofilling the admin username/password into the Turnstile key fields.
 * The obscure element does not render "autocomplete" itself, so the attribute is added to the input tag.
 */
class NoAutofillField extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = (string) $element->getElementHtml();
        if (preg_match('/^\s*<input\b[^>]*\bautocomplete=/i', $html)) {
            return $html;
        }

        $value = $element->getType() === 'password' ? 'new-password' : 'off';

        return (string) preg_replace('/<input\b/i', '<input autocomplete="' . $value . '"', $html, 1);
    }
}
