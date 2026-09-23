<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Model\Queue;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceMessageInterface;
use AlpineCommerce\AutoInvoice\Api\InvoiceAutomationInterface;
use Psr\Log\LoggerInterface;

/**
 * Consumes invoice processing messages and delegates to the service layer.
 *
 * Idempotency is guaranteed by InvoiceAutomationService::processOrder()
 * which checks canInvoice() before creating an invoice.
 */
class InvoiceConsumer
{
    public function __construct(
        private readonly InvoiceAutomationInterface $invoiceService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(InvoiceMessageInterface $message): void
    {
        $startTime = microtime(true);
        $correlationId = $message->getCorrelationId() ?? '';
        $orderId = $message->getOrderId();

        $this->logger->info('Invoice consumer started', [
            'correlation_id' => $correlationId,
            'entity_id' => $orderId,
            'message_type' => 'invoice.process',
            'status' => 'started',
        ]);

        try {
            $result = $this->invoiceService->processOrder($orderId);

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->info('Invoice consumer completed', [
                'correlation_id' => $correlationId,
                'entity_id' => $orderId,
                'message_type' => 'invoice.process',
                'status' => $result->getStatus(),
                'execution_time_ms' => $executionTime,
            ]);
        } catch (\Throwable $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->error('Invoice consumer failed', [
                'correlation_id' => $correlationId,
                'entity_id' => $orderId,
                'message_type' => 'invoice.process',
                'status' => 'error',
                'execution_time_ms' => $executionTime,
                'exception' => $e->getMessage(),
                'exception_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
