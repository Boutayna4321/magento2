<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Test\Unit\Model\Rma;

use AlpineCommerce\Rma\Model\Rma\State;
use AlpineCommerce\Rma\Api\Data\RmaInterface;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testCanTransitionFromPending(): void
    {
        $this->assertTrue(State::canTransition(RmaInterface::STATUS_PENDING, RmaInterface::STATUS_APPROVED));
        $this->assertTrue(State::canTransition(RmaInterface::STATUS_PENDING, RmaInterface::STATUS_REJECTED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_PENDING, RmaInterface::STATUS_RECEIVED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_PENDING, RmaInterface::STATUS_REFUNDED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_PENDING, RmaInterface::STATUS_CLOSED));
    }

    public function testCanTransitionFromApproved(): void
    {
        $this->assertTrue(State::canTransition(RmaInterface::STATUS_APPROVED, RmaInterface::STATUS_RECEIVED));
        $this->assertTrue(State::canTransition(RmaInterface::STATUS_APPROVED, RmaInterface::STATUS_REJECTED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_APPROVED, RmaInterface::STATUS_REFUNDED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_APPROVED, RmaInterface::STATUS_CLOSED));
    }

    public function testCanTransitionFromReceived(): void
    {
        $this->assertTrue(State::canTransition(RmaInterface::STATUS_RECEIVED, RmaInterface::STATUS_REFUNDED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_RECEIVED, RmaInterface::STATUS_APPROVED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_RECEIVED, RmaInterface::STATUS_CLOSED));
    }

    public function testCanTransitionFromRefunded(): void
    {
        $this->assertTrue(State::canTransition(RmaInterface::STATUS_REFUNDED, RmaInterface::STATUS_CLOSED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_REFUNDED, RmaInterface::STATUS_APPROVED));
    }

    public function testCannotTransitionFromClosed(): void
    {
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_CLOSED, RmaInterface::STATUS_APPROVED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_CLOSED, RmaInterface::STATUS_REFUNDED));
        $this->assertFalse(State::canTransition(RmaInterface::STATUS_CLOSED, RmaInterface::STATUS_RECEIVED));
    }

    public function testGetAvailableTransitions(): void
    {
        $transitions = State::getAvailableTransitions(RmaInterface::STATUS_PENDING);
        $this->assertContains(RmaInterface::STATUS_APPROVED, $transitions);
        $this->assertContains(RmaInterface::STATUS_REJECTED, $transitions);
        $this->assertNotContains(RmaInterface::STATUS_REFUNDED, $transitions);
    }
}
