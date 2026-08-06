<?php
namespace Cartware\Training\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class OrderPlacedAfter implements ObserverInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            // Post-order logic here (ERP sync, custom emails, etc.)
        } catch (\Exception $e) {
            $this->logger->error('Training OrderPlacedAfter: ' . $e->getMessage());
        }

        return $this;
    }
}
