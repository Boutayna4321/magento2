<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model;

use AlpineCommerce\Gdpr\Api\GdprDeleteInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class GdprDeleteService implements GdprDeleteInterface
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    /**
     * Anonymize the personal data of a customer instead of hard-deleting,
     * so order history and foreign keys stay consistent.
     *
     * @param int $customerId
     * @return bool
     */
    public function delete(int $customerId): bool
    {
        $customer = $this->customerRepository->getById($customerId);

        $customer->setEmail('deleted-' . $customerId . '@anonymized.local');
        $customer->setFirstname('Deleted');
        $customer->setLastname('User');
        $customer->setTaxvat(null);
        $customer->setDob(null);

        $this->customerRepository->save($customer);

        return true;
    }
}
