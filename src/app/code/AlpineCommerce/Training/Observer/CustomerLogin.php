<?php
declare(strict_types=1);

namespace AlpineCommerce\Training\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class CustomerLogin implements ObserverInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        try {
            $customer = $observer->getEvent()->getCustomer();
            // Post-login logic here (last-login tracking, welcome message, etc.)
        } catch (\Exception $e) {
            $this->logger->error('Training CustomerLogin: ' . $e->getMessage());
        }
    }
}
