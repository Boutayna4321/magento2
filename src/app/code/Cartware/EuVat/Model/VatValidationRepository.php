<?php
declare(strict_types=1);

namespace Cartware\EuVat\Model;

use Cartware\EuVat\Api\Data\VatValidationInterface;
use Cartware\EuVat\Api\VatValidationRepositoryInterface;
use Cartware\EuVat\Model\ResourceModel\VatValidation as VatValidationResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class VatValidationRepository implements VatValidationRepositoryInterface
{
    public function __construct(
        private readonly VatValidationFactory $vatValidationFactory,
        private readonly VatValidationResource $vatValidationResource
    ) {
    }

    /**
     * @param VatValidationInterface $validation
     * @return VatValidationInterface
     */
    public function save(VatValidationInterface $validation): VatValidationInterface
    {
        try {
            $this->vatValidationResource->save($validation);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save VAT validation record.'), $e);
        }

        return $validation;
    }

    /**
     * @param int $id
     * @return VatValidationInterface
     */
    public function getById(int $id): VatValidationInterface
    {
        /** @var VatValidationInterface $validation */
        $validation = $this->vatValidationFactory->create();
        $this->vatValidationResource->load($validation, $id);

        if (!$validation->getId()) {
            throw new NoSuchEntityException(__('VAT validation record with ID "%1" does not exist.', $id));
        }

        return $validation;
    }
}
