<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Model\Queue;

use AlpineCommerce\CreditMemo\Api\CreditMemoAutomationInterface;
use AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface;
use Psr\Log\LoggerInterface;

/**
 * Consumes credit memo processing messages and delegates to the service layer.
 *
 * Idempotency is guaranteed by CreditMemoService::processCancellation()
 * which checks canCreditmemo() and refundable quantities.
 */
class CreditMemoConsumer
{
    public function __construct(
        private readonly CreditMemoAutomationInterface $creditMemoService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(CreditMemoMessageInterface $message): void
    {
        $startTime = microtime(true);
        $correlationId = $message->getCorrelationId() ?? '';
        $orderId = $message->getOrderId();

        $this->logger->info('CreditMemo consumer started', [
            'correlation_id' => $correlationId,
            'entity_id' => $orderId,
            'message_type' => 'creditmemo.process',
            'status' => 'started',
        ]);

        try {
            $result = $this->creditMemoService->processCancellation($orderId);

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->info('CreditMemo consumer completed', [
                'correlation_id' => $correlationId,
                'entity_id' => $orderId,
                'message_type' => 'creditmemo.process',
                'status' => $result->getStatus(),
                'execution_time_ms' => $executionTime,
            ]);
        } catch (\Throwable $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->error('CreditMemo consumer failed', [
                'correlation_id' => $correlationId,
                'entity_id' => $orderId,
                'message_type' => 'creditmemo.process',
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
