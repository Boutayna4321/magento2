<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Plugin;

use AlpineCommerce\ProductLabels\Block\Product\Labels as LabelsBlock;
use Magento\Catalog\Block\Product\ListProduct as CatalogListProduct;
use Magento\Catalog\Model\Product;

class CatalogBlock
{
    public function __construct(private readonly LabelsBlock $labelsBlock)
    {
    }

    public function aroundGetProductDetailsHtml(
        CatalogListProduct $subject,
        \Closure $proceed,
        Product $product
    ): string {
        $html = $proceed($product);
        $labelsHtml = $this->labelsBlock->getLabelsHtml($product);

        if ($labelsHtml) {
            $html = $labelsHtml . $html;
        }

        return $html;
    }
}
