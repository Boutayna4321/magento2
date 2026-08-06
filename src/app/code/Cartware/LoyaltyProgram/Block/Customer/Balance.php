<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Block\Customer;

use Cartware\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Balance extends Template
{
    /**
     * @param Context $context
     * @param LoyaltyBalanceRepositoryInterface $balanceRepository
     * @param CustomerSession $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository,
        private readonly CustomerSession $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Points balance of the logged-in customer.
     *
     * @return int
     */
    public function getBalance(): int
    {
        $customerId = (int) $this->customerSession->getCustomerId();
        if (!$customerId) {
            return 0;
        }

        return $this->balanceRepository->getByCustomerId($customerId)->getPoints();
    }
}
