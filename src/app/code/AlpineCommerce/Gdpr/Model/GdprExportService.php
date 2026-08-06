<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model;

use AlpineCommerce\Gdpr\Api\ConsentManagementInterface;
use AlpineCommerce\Gdpr\Api\GdprExportInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;

class GdprExportService implements GdprExportInterface
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly ConsentManagementInterface $consentManagement
    ) {
    }

    /**
     * @param int $customerId
     * @return array<string, mixed>
     */
    public function export(int $customerId): array
    {
        $customer = $this->customerRepository->getById($customerId);

        return [
            'customer' => [
                'id' => (int) $customer->getId(),
                'email' => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'created_at' => $customer->getCreatedAt(),
                'default_billing' => $customer->getDefaultBilling(),
                'default_shipping' => $customer->getDefaultShipping(),
            ],
            'addresses' => $this->getAddresses($customer),
            'orders' => $this->getOrders($customerId),
            'consent_history' => $this->consentManagement->getHistory($customerId),
        ];
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return array<int, array<string, mixed>>
     */
    private function getAddresses(\Magento\Customer\Api\Data\CustomerInterface $customer): array
    {
        $result = [];
        foreach ($customer->getAddresses() ?: [] as $address) {
            $result[] = [
                'id' => (int) $address->getId(),
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'street' => implode(', ', $address->getStreet() ?: []),
                'city' => $address->getCity(),
                'postcode' => $address->getPostcode(),
                'country' => $address->getCountryId(),
                'telephone' => $address->getTelephone(),
            ];
        }

        return $result;
    }

    /**
     * @param int $customerId
     * @return array<int, array<string, mixed>>
     */
    private function getOrders(int $customerId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId)
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria)->getItems();

        $result = [];
        foreach ($orders as $order) {
            $result[] = [
                'increment_id' => $order->getIncrementId(),
                'status' => $order->getStatus(),
                'total' => $order->getGrandTotal(),
                'currency' => $order->getOrderCurrencyCode(),
                'created_at' => $order->getCreatedAt(),
            ];
        }

        return $result;
    }
}
