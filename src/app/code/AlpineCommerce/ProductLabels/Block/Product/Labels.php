<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Block\Product;

use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Labels extends AbstractProduct
{
    public const XML_PATH_ENABLED = 'productlabels/general/enabled';

    public function __construct(
        Context $context,
        private readonly ProductLabelRepositoryInterface $labelRepository,
        private readonly ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getLabelsHtml(Product $product): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $labels = $this->labelRepository->getLabelsByProductId((int) $product->getId());
        if (empty($labels)) {
            return '';
        }

        $html = '<div class="product-labels">';
        foreach ($labels as $label) {
            $position = (string) ($label['position'] ?? 'top-left');
            $backgroundColor = (string) ($label['color'] ?? '#000000');
            $textColor = (string) ($label['text_color'] ?? '#ffffff');

            $html .= '<span class="product-label product-label--' . $this->escapeHtmlAttr($position) . '" '
                . 'style="background-color:' . $this->escapeHtmlAttr($backgroundColor) . ';'
                . 'color:' . $this->escapeHtmlAttr($textColor) . '">'
                . $this->escapeHtml((string) ($label['name'] ?? ''))
                . '</span>';
        }
        $html .= '</div>';

        return $html;
    }

    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );
    }

    private function getStoreId(): int
    {
        return (int) $this->_storeManager->getStore()->getId();
    }
}
