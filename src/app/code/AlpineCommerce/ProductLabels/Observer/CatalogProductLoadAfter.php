<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;

class CatalogProductLoadAfter implements ObserverInterface
{
    public function __construct(private readonly ProductLabelRepositoryInterface $labelRepository) {}

    public function execute(Observer $observer): void {
        $product = $observer->getEvent()->getProduct();
        if ($product) {
            $labels = $this->labelRepository->getLabelsByProductId((int) $product->getId());
            $product->setData("product_labels", $labels);
        }
    }
}
