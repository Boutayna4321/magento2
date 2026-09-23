<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Model\Queue;

use AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceMessageInterface;
use AlpineCommerce\PartialInvoice\Api\PartialInvoiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Consumes partial invoice processing messages and delegates to the service layer.
 *
 * Idempotency is guaranteed by PartialInvoiceService::processOrder()
 * which checks canInvoice() and calculates remaining invoiced quantities.
 */
class PartialInvoiceConsumer
{
    public function __construct(
        private readonly PartialInvoiceInterface $partialInvoiceService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(PartialInvoiceMessageInterface $message): void
    {
        $startTime = microtime(true);
        $correlationId = $message->getCorrelationId() ?? '';
        $orderId = $message->getOrderId();

        $this->logger->info('PartialInvoice consumer started', [
            'correlation_id' => $correlationId,
            'entity_id' => $orderId,
            'message_type' => 'partialinvoice.process',
            'status' => 'started',
        ]);

        try {
            $result = $this->partialInvoiceService->processOrder($orderId);

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->info('PartialInvoice consumer completed', [
                'correlation_id' => $correlationId,
                'entity_id' => $orderId,
                'message_type' => 'partialinvoice.process',
                'status' => $result->getStatus(),
                'execution_time_ms' => $executionTime,
            ]);
        } catch (\Throwable $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->error('PartialInvoice consumer failed', [
                'correlation_id' => $correlationId,
                'entity_id' => $orderId,
                'message_type' => 'partialinvoice.process',
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
