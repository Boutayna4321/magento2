<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Api;

interface StoreAvailabilityInterface
{
    /**
     * Return the pickup stores where ALL given items are available.
     *
     * @param array $items map of sku => requested qty
     * @return array[] list of stores (source_code, name, street, city, region, postcode, country_id, phone, latitude, longitude, opening_hours)
     */
    public function getAvailableStores(array $items): array;
}
