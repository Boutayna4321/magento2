<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Model\InMemory;

use Cartware\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface;
use Cartware\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use Cartware\LoyaltyProgram\Model\LoyaltyBalance;

/**
 * Temporary in-memory implementation used to validate the
 * Controller/Repository flow before the real DB storage exists.
 */
class LoyaltyBalanceRepository implements LoyaltyBalanceRepositoryInterface
{
    /**
     * @var array<string, int>
     */
    private static array $storage = [];

    /**
     * @param LoyaltyBalanceInterface|null $fallback
     */
    public function __construct(
        private readonly ?LoyaltyBalanceInterface $fallback = null
    ) {
    }

    /**
     * @param int $customerId
     * @return LoyaltyBalanceInterface
     */
    public function getByCustomerId(int $customerId): LoyaltyBalanceInterface
    {
        if (!isset(self::$storage[$customerId])) {
            self::$storage[$customerId] = $this->fallback ? $this->fallback->getPoints() : 0;
        }

        return (new LoyaltyBalance())
            ->setCustomerId($customerId)
            ->setPoints(self::$storage[$customerId]);
    }

    /**
     * @param LoyaltyBalanceInterface $balance
     * @return LoyaltyBalanceInterface
     */
    public function save(LoyaltyBalanceInterface $balance): LoyaltyBalanceInterface
    {
        self::$storage[$balance->getCustomerId()] = $balance->getPoints();

        return $balance;
    }
}
