<?php
namespace Cartware\Training\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class CheckoutBefore implements ObserverInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            // Pre-checkout logic here (block checkout, redirect, etc.)
        } catch (\Exception $e) {
            $this->logger->error('Training CheckoutBefore: ' . $e->getMessage());
        }

        return $this;
    }
}
