<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Service;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Single source of truth for the RMA return-window calculation
 * (Step 3: must not be duplicated across observers and controllers).
 */
class ReturnWindow
{
    public function __construct(
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function computeDeadline(?string $createdAt, int $allowReturnDays): string
    {
        $base = new \DateTime($createdAt ?: 'now', new \DateTimeZone('UTC'));
        $base->add(new \DateInterval('P' . max(0, $allowReturnDays) . 'D'));

        return $base->format('Y-m-d H:i:s');
    }

    public function isExpired(string $allowedUntil): bool
    {
        return $this->timezone->date()->format('Y-m-d H:i:s') > $allowedUntil;
    }
}
