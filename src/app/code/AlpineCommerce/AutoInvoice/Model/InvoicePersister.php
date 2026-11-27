<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Model;

use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;

/**
 * Saves an order and its new invoice the way Magento's admin invoice controller does.
 *
 * - setIsInProcess(true): the order state handler only moves a "new" order to
 *   "processing" when this flag is set.
 * - Repositories instead of $model->save(): plugins on the repositories run, e.g.
 *   LoyaltyProgram awards points after InvoiceRepositoryInterface::save().
 * - Order first: the invoice needs the order id.
 */
class InvoicePersister
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository
    ) {
    }

    public function persist(Order $order, Invoice $invoice): void
    {
        $order->setIsInProcess(true);
        $this->orderRepository->save($order);
        $this->invoiceRepository->save($invoice);
    }
}
