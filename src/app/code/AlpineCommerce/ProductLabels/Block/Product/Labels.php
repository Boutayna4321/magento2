<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Block\Product;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Block\Product\AbstractProduct;
use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Labels extends AbstractProduct
{
    public function __construct(Context $context, private readonly ProductLabelRepositoryInterface $labelRepository, private readonly TimezoneInterface $timezone, array $data = []) {
        parent::__construct($context, $data);
    }

    public function getLabelsHtml(Product $product): string {
        $labels = $this->labelRepository->getLabelsByProductId((int) $product->getId());
        if (empty($labels)) { return ""; }

        $html = "<div class=\"product-labels\" style=\"position:absolute;top:10px;left:10px;z-index:10;\">";
        foreach ($labels as $label) {
            $position = $label["position"] ?? "top-left";
            $html .= "<span class=\"product-label\" style=\"background-color:" . ($label["color"] ?? "#000000") . ";color:" . ($label["text_color"] ?? "#ffffff") . ";position:absolute;";
            if ($position === "top-left") { $html .= "top:5px;left:5px;"; } elseif ($position === "top-right") { $html .= "top:5px;right:5px;"; } elseif ($position === "bottom-left") { $html .= "bottom:5px;left:5px;"; } else { $html .= "bottom:5px;right:5px;"; }
            $html .= "padding:4px 8px;font-size:12px;font-weight:bold;text-transform:uppercase;border-radius:2px;line-height:1;white-space:nowrap;z-index:10;\">" . $this->escapeHtml($label["name"]) . "</span>";
        }
        $html .= "</div>";
        return $html;
    }
}
