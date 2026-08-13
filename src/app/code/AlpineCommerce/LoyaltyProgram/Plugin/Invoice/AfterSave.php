<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Plugin\Invoice;

use AlpineCommerce\LoyaltyProgram\Service\PointsCalculator;
use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Logger\Logger;
use AlpineCommerce\LoyaltyProgram\Model\LoyaltyOrderPointsFactory;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints\CollectionFactory;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints as LoyaltyOrderPointsResource;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class AfterSave
{
    public const LEDGER_TYPE_EARN = 'earn';

    public function __construct(
        private readonly Logger $logger,
        private readonly PointsCalculator $pointsHelper,
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository,
        private readonly LoyaltyOrderPointsFactory $loyaltyOrderPointsFactory,
        private readonly LoyaltyOrderPointsResource $loyaltyOrderPointsResource,
        private readonly CollectionFactory $loyaltyOrderPointsCollectionFactory
    ) {
    }

    public function afterSave(InvoiceRepositoryInterface $subject, InvoiceInterface $result): InvoiceInterface
    {
        if (!$result || !$result->getOrder()) {
            return $result;
        }

        $order = $result->getOrder();
        if (!$order->getCustomerId()) {
            return $result;
        }

        $earned = $this->loyaltyOrderPointsCollectionFactory->create()
            ->addFieldToFilter('order_id', (int) $order->getId())
            ->addFieldToFilter('type', self::LEDGER_TYPE_EARN)
            ->getFirstItem();

        if ($earned->getId()) {
            return $result;
        }

        $points = $this->pointsHelper->calculatePoints((float) $order->getGrandTotal());
        if ($points <= 0) {
            return $result;
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

        return $result;
    }
}
