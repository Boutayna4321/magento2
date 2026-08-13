<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Plugin\Order;

use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Logger\Logger;
use AlpineCommerce\LoyaltyProgram\Model\LoyaltyOrderPointsFactory;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints as LoyaltyOrderPointsResource;
use AlpineCommerce\LoyaltyProgram\Model\Total\Quote\LoyaltyDiscount;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class AfterSave
{
    public const LEDGER_TYPE_DEDUCT = 'deduct';

    public function __construct(
        private readonly Logger $logger,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository,
        private readonly LoyaltyOrderPointsFactory $loyaltyOrderPointsFactory,
        private readonly LoyaltyOrderPointsResource $loyaltyOrderPointsResource
    ) {
    }

    public function afterSave(OrderRepositoryInterface $subject, OrderInterface $result): OrderInterface
    {
        if (!$result || !$result->getQuoteId() || !$result->getCustomerId()) {
            return $result;
        }

        $ledger = $this->loyaltyOrderPointsFactory->create();
        $this->loyaltyOrderPointsResource->load($ledger, $result->getId(), 'order_id');

        if ($ledger->getType() === self::LEDGER_TYPE_DEDUCT) {
            return $result;
        }

        $quote = $this->quoteRepository->get((int) $result->getQuoteId());
        $pointsUsed = (int) $quote->getData(LoyaltyDiscount::QUOTE_FIELD_POINTS_USED);
        if ($pointsUsed <= 0) {
            return $result;
        }

        $balance = $this->balanceRepository->getByCustomerId((int) $result->getCustomerId());
        $balance->setPoints(max(0, $balance->getPoints() - $pointsUsed));
        $this->balanceRepository->save($balance);

        $ledger->setData('order_id', (int) $result->getId());
        $ledger->setData('type', self::LEDGER_TYPE_DEDUCT);
        $ledger->setData('points', -$pointsUsed);
        $this->loyaltyOrderPointsResource->save($ledger);

        $this->logger->info(
            sprintf('commande %s a déduit %d points pour le client %d (solde %d)',
                $result->getIncrementId(), $pointsUsed, (int) $result->getCustomerId(), $balance->getPoints())
        );

        return $result;
    }
}
