<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\Config\Source;

use AlpineCommerce\Turnstile\Model\Config\Source\FailureModeOverride;
use PHPUnit\Framework\TestCase;

class FailureModeOverrideTest extends TestCase
{
    public function testOffersInheritThenClosedThenOpen(): void
    {
        $values = array_column((new FailureModeOverride())->toOptionArray(), 'value');

        $this->assertSame(
            [FailureModeOverride::INHERIT, FailureModeOverride::CLOSED, FailureModeOverride::OPEN],
            $values
        );
        $this->assertSame('', FailureModeOverride::INHERIT);
    }
}
