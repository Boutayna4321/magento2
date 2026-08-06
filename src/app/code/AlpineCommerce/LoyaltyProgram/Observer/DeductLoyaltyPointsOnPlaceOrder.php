<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Observer;

use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Logger\Logger;
use AlpineCommerce\LoyaltyProgram\Model\LoyaltyOrderPointsFactory;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints as LoyaltyOrderPointsResource;
use AlpineCommerce\LoyaltyProgram\Model\Total\Quote\LoyaltyDiscount;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;

class DeductLoyaltyPointsOnPlaceOrder implements ObserverInterface
{
    public const LEDGER_TYPE_DEDUCT = 'deduct';

    /**
     * @param Logger $logger
     * @param CartRepositoryInterface $quoteRepository
     * @param LoyaltyBalanceRepositoryInterface $balanceRepository
     * @param LoyaltyOrderPointsFactory $loyaltyOrderPointsFactory
     * @param LoyaltyOrderPointsResource $loyaltyOrderPointsResource
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository,
        private readonly LoyaltyOrderPointsFactory $loyaltyOrderPointsFactory,
        private readonly LoyaltyOrderPointsResource $loyaltyOrderPointsResource
    ) {
    }

    /**
     * Deduct the loyalty points used from the customer balance once the order
     * is saved. sales_order_save_after fires on every order save, so the
     * ledger (unique order_id+type=deduct) is what makes this idempotent.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getQuoteId() || !$order->getCustomerId()) {
            return;
        }

        $ledger = $this->loyaltyOrderPointsFactory->create();
        $this->loyaltyOrderPointsResource->load($ledger, $order->getId(), 'order_id');

        if ($ledger->getType() === self::LEDGER_TYPE_DEDUCT) {
            return;
        }

        $quote = $this->quoteRepository->get((int) $order->getQuoteId());
        $pointsUsed = (int) $quote->getData(LoyaltyDiscount::QUOTE_FIELD_POINTS_USED);
        if ($pointsUsed <= 0) {
            return;
        }

        $balance = $this->balanceRepository->getByCustomerId((int) $order->getCustomerId());
        $balance->setPoints(max(0, $balance->getPoints() - $pointsUsed));
        $this->balanceRepository->save($balance);

        $ledger->setData('order_id', (int) $order->getId());
        $ledger->setData('type', self::LEDGER_TYPE_DEDUCT);
        $ledger->setData('points', -$pointsUsed);
        $this->loyaltyOrderPointsResource->save($ledger);

        $this->logger->info(
            sprintf('commande %s a déduit %d points pour le client %d (solde %d)',
                $order->getIncrementId(), $pointsUsed, (int) $order->getCustomerId(), $balance->getPoints())
        );
    }
}
