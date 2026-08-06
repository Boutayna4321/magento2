<?php
namespace Cartware\Training\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class CustomerLogin implements ObserverInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();
            // Post-login logic here (last-login tracking, welcome message, etc.)
        } catch (\Exception $e) {
            $this->logger->error('Training CustomerLogin: ' . $e->getMessage());
        }

        return $this;
    }
}
