<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Message interface for async customer VIP recalculation.
 *
 * @api
 */
interface VipRecalculationMessageInterface extends ExtensibleDataInterface
{
    public const KEY_CUSTOMER_ID = 'customer_id';
    public const KEY_SCOPE = 'scope';
    public const KEY_STORE_ID = 'store_id';
    public const KEY_CORRELATION_ID = 'correlation_id';
    public const KEY_CREATED_AT = 'created_at';

    public const SCOPE_CUSTOMER = 'customer';
    public const SCOPE_BATCH = 'batch';
    public const SCOPE_ALL = 'all';

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * @param int $customerId
     * @return \AlpineCommerce\CustomerCare\Api\Data\VipRecalculationMessageInterface
     */
    public function setCustomerId(int $customerId): self;

    /**
     * @return string
     */
    public function getScope(): string;

    /**
     * @param string $scope
     * @return \AlpineCommerce\CustomerCare\Api\Data\VipRecalculationMessageInterface
     */
    public function setScope(string $scope): self;

    /**
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * @param int|null $storeId
     * @return \AlpineCommerce\CustomerCare\Api\Data\VipRecalculationMessageInterface
     */
    public function setStoreId(?int $storeId): self;

    /**
     * @return string|null
     */
    public function getCorrelationId(): ?string;

    /**
     * @param string|null $correlationId
     * @return \AlpineCommerce\CustomerCare\Api\Data\VipRecalculationMessageInterface
     */
    public function setCorrelationId(?string $correlationId): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $createdAt
     * @return \AlpineCommerce\CustomerCare\Api\Data\VipRecalculationMessageInterface
     */
    public function setCreatedAt(string $createdAt): self;
}
