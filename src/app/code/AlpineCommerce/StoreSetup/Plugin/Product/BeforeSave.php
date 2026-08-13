<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreSetup\Plugin\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class BeforeSave
{
    private readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function beforeSave(ProductRepositoryInterface $subject, ProductInterface $product, bool $saveOptions = false): array
    {
        try {
            if (empty($product->getShortDescription())) {
                $product->setShortDescription('Auto-generated description for ' . $product->getName());
            }
        } catch (\Exception $e) {
            $this->logger->error('StoreSetup Product BeforeSave: ' . $e->getMessage());
        }

        return [$product, $saveOptions];
    }
}
