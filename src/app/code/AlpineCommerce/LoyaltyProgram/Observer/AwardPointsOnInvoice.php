<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Observer;

use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Helper\Points;
use AlpineCommerce\LoyaltyProgram\Logger\Logger;
use AlpineCommerce\LoyaltyProgram\Model\LoyaltyOrderPointsFactory;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints\CollectionFactory;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints as LoyaltyOrderPointsResource;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Invoice;

class AwardPointsOnInvoice implements ObserverInterface
{
    public const LEDGER_TYPE_EARN = 'earn';

    /**
     * @param Logger $logger
     * @param Points $pointsHelper
     * @param LoyaltyBalanceRepositoryInterface $balanceRepository
     * @param LoyaltyOrderPointsFactory $loyaltyOrderPointsFactory
     * @param LoyaltyOrderPointsResource $loyaltyOrderPointsResource
     * @param CollectionFactory $loyaltyOrderPointsCollectionFactory
     */
    public function __construct(
        private readonly Logger $logger,
        private readonly Points $pointsHelper,
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository,
        private readonly LoyaltyOrderPointsFactory $loyaltyOrderPointsFactory,
        private readonly LoyaltyOrderPointsResource $loyaltyOrderPointsResource,
        private readonly CollectionFactory $loyaltyOrderPointsCollectionFactory
    ) {
    }

    /**
     * Award loyalty points when an order invoice is saved (order paid).
     *
     * The alpinecommerce_loyalty_order_points ledger (type=earn) makes this idempotent:
     * the event can fire several times (multiple invoices, retries) without
     * awarding twice for the same order.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Invoice $invoice */
        $invoice = $observer->getEvent()->getInvoice();
        if (!$invoice || !$invoice->getOrder()) {
            return;
        }

        $order = $invoice->getOrder();
        if (!$order->getCustomerId()) {
            return;
        }

        $earned = $this->loyaltyOrderPointsCollectionFactory->create()
            ->addFieldToFilter('order_id', (int) $order->getId())
            ->addFieldToFilter('type', self::LEDGER_TYPE_EARN)
            ->getFirstItem();

        if ($earned->getId()) {
            return;
        }

        $points = $this->pointsHelper->calculatePoints((float) $order->getGrandTotal());
        if ($points <= 0) {
            return;
        }

        $orderPoints = $this->loyaltyOrderPointsFactory->create();
        $orderPoints->setData('order_id', (int) $order->getId());
        $orderPoints->setData('type', self::LEDGER_TYPE_EARN);
        $orderPoints->setData('points', $points);
        $this->loyaltyOrderPointsResource->save($orderPoints);

        $balance = $this->balanceRepository->getByCustomerId((int) $order->getCustomerId());
        $this->balanceRepository->save($balance->setPoints($balance->getPoints() + $points));

        $this->logger->info(
            sprintf('commande %s a généré %d points pour le client %d',
                $order->getIncrementId(), $points, (int) $order->getCustomerId())
        );
    }
}
