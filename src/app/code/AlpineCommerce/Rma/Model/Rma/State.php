<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model\Rma;

use AlpineCommerce\Rma\Api\Data\RmaInterface;

final class State
{
    /**
     * Allowed status transitions keyed by current status.
     * Any transition not listed here is rejected (Step 5: invalid transitions
     * such as REJECTED -> REFUNDED, REFUNDED -> REQUESTED, REQUESTED -> REFUNDED
     * are impossible by construction).
     */
    private const TRANSITIONS = [
        RmaInterface::STATUS_PENDING => [
            RmaInterface::STATUS_APPROVED,
            RmaInterface::STATUS_REJECTED,
        ],
        RmaInterface::STATUS_APPROVED => [
            RmaInterface::STATUS_RETURNED,
            RmaInterface::STATUS_RECEIVED,
        ],
        RmaInterface::STATUS_RETURNED => [
            RmaInterface::STATUS_RECEIVED,
            RmaInterface::STATUS_REJECTED,
        ],
        RmaInterface::STATUS_RECEIVED => [
            RmaInterface::STATUS_REFUNDED,
        ],
        RmaInterface::STATUS_REFUNDED => [
            RmaInterface::STATUS_CLOSED,
        ],
        RmaInterface::STATUS_REJECTED => [
            RmaInterface::STATUS_CLOSED,
        ],
        RmaInterface::STATUS_CLOSED => [],
    ];

    public static function getAllowedTargets(string $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }

    public static function canTransition(string $from, string $to): bool
    {
        // A transition from a state to itself is not a valid transition
        // (it would let duplicate/refunded RMAs re-trigger actions).
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function isFinal(string $status): bool
    {
        // Final = no outgoing transitions. Derived from the transition graph
        // (TRANSITIONS) so this can never contradict canTransition() again.
        // REFUNDED and REJECTED are NOT final: each may transition to CLOSED.
        return self::getAllowedTargets($status) === [];
    }
}
