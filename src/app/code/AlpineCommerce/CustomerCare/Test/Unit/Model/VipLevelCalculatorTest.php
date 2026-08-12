<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Test\Unit\Model;

use AlpineCommerce\CustomerCare\Model\Config;
use AlpineCommerce\CustomerCare\Model\VipLevel;
use AlpineCommerce\CustomerCare\Model\VipLevelCalculator;
use PHPUnit\Framework\TestCase;

class VipLevelCalculatorTest extends TestCase
{
    private function createConfig(float $bronze, float $silver, float $gold): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('getBronzeThreshold')->willReturn($bronze);
        $config->method('getSilverThreshold')->willReturn($silver);
        $config->method('getGoldThreshold')->willReturn($gold);
        return $config;
    }

    public function testReturnsGoldAtOrAboveGoldThreshold(): void
    {
        $calculator = new VipLevelCalculator($this->createConfig(100.0, 500.0, 1000.0));

        self::assertSame(VipLevel::GOLD, $calculator->calculate(1000.0));
        self::assertSame(VipLevel::GOLD, $calculator->calculate(2500.5));
    }

    public function testReturnsSilverAtOrAboveSilverThreshold(): void
    {
        $calculator = new VipLevelCalculator($this->createConfig(100.0, 500.0, 1000.0));

        self::assertSame(VipLevel::SILVER, $calculator->calculate(500.0));
        self::assertSame(VipLevel::SILVER, $calculator->calculate(999.99));
    }

    public function testReturnsBronzeAtOrAboveBronzeThreshold(): void
    {
        $calculator = new VipLevelCalculator($this->createConfig(100.0, 500.0, 1000.0));

        self::assertSame(VipLevel::BRONZE, $calculator->calculate(100.0));
        self::assertSame(VipLevel::BRONZE, $calculator->calculate(499.99));
    }

    public function testReturnsNoneBelowBronzeThreshold(): void
    {
        $calculator = new VipLevelCalculator($this->createConfig(100.0, 500.0, 1000.0));

        self::assertSame(VipLevel::NONE, $calculator->calculate(0.0));
        self::assertSame(VipLevel::NONE, $calculator->calculate(99.99));
    }

    public function testPassesWebsiteIdThroughToConfig(): void
    {
        $config = $this->createMock(Config::class);
        $config->expects(self::once())->method('getGoldThreshold')->with(5)->willReturn(1000.0);
        $config->expects(self::once())->method('getSilverThreshold')->with(5)->willReturn(500.0);
        $config->expects(self::once())->method('getBronzeThreshold')->with(5)->willReturn(100.0);

        $calculator = new VipLevelCalculator($config);

        self::assertSame(VipLevel::NONE, $calculator->calculate(10.0, 5));
    }
}
