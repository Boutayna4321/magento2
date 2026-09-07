<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Block\Adminhtml\Balance;

use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

class View extends Template
{
    /**
     * @param Context $context
     * @param LoyaltyBalanceRepositoryInterface $balanceRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return (int) $this->getRequest()->getParam('customer_id', 0);
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        $customerId = $this->getCustomerId();

        if ($customerId <= 0) {
            return null;
        }

        try {
            return $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * @return int
     */
    public function getBalance(): int
    {
        $customerId = $this->getCustomerId();

        if ($customerId <= 0) {
            return 0;
        }

        return $this->balanceRepository->getByCustomerId($customerId)->getPoints();
    }

    /**
     * @return string
     */
    public function getViewUrl(): string
    {
        return $this->getUrl('loyalty/balance/view', ['customer_id' => $this->getCustomerId()]);
    }
}
