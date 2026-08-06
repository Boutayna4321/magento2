<?php
namespace Cartware\Training\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class ProductSaveAfter implements ObserverInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();

            if (empty($product->getShortDescription())) {
                $product->setShortDescription('Auto-generated description for ' . $product->getName());
            }
        } catch (\Exception $e) {
            $this->logger->error('Training ProductSaveAfter: ' . $e->getMessage());
        }

        return $this;
    }
}
