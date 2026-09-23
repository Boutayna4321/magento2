<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model\Queue;

use AlpineCommerce\CustomerCare\Api\CustomerCareInterface;
use AlpineCommerce\CustomerCare\Api\Data\VipRecalculationMessageInterface;
use Psr\Log\LoggerInterface;

/**
 * Consumes VIP recalculation messages and delegates to the CustomerCareService.
 *
 * Idempotency is guaranteed by CustomerCareService::recalculateVipStatus()
 * which uses the deterministic lifetime-spent sum.
 */
class VipRecalculationConsumer
{
    public function __construct(
        private readonly CustomerCareInterface $customerCareService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(VipRecalculationMessageInterface $message): void
    {
        $startTime = microtime(true);
        $correlationId = $message->getCorrelationId() ?? '';
        $scope = $message->getScope();

        $this->logger->info('VIP recalculation consumer started', [
            'correlation_id' => $correlationId,
            'message_type' => 'vip.recalculate',
            'scope' => $scope,
            'status' => 'started',
        ]);

        try {
            if ($scope === VipRecalculationMessageInterface::SCOPE_CUSTOMER) {
                $customerId = (int) $message->getCustomerId();
                $this->customerCareService->recalculateVipStatus($customerId);
            }

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->info('VIP recalculation consumer completed', [
                'correlation_id' => $correlationId,
                'message_type' => 'vip.recalculate',
                'scope' => $scope,
                'entity_id' => $message->getCustomerId(),
                'status' => 'completed',
                'execution_time_ms' => $executionTime,
            ]);
        } catch (\Throwable $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->logger->error('VIP recalculation consumer failed', [
                'correlation_id' => $correlationId,
                'message_type' => 'vip.recalculate',
                'scope' => $scope,
                'entity_id' => $message->getCustomerId(),
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
