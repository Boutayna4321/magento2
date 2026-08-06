<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Model;

use AlpineCommerce\EuVat\Api\Data\VatValidationInterface;
use AlpineCommerce\EuVat\Api\VatValidationInterface as VatValidationServiceInterface;
use AlpineCommerce\EuVat\Api\VatValidationRepositoryInterface;
use AlpineCommerce\EuVat\Model\VatValidationFactory as VatValidationModelFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class VatValidationService implements VatValidationServiceInterface
{
    public function __construct(
        private readonly ViesClient $viesClient,
        private readonly VatValidationModelFactory $vatValidationFactory,
        private readonly VatValidationRepositoryInterface $vatValidationRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function validate(string $countryId, string $vatNumber): VatValidationInterface
    {
        $countryId = strtoupper(trim($countryId));
        $vatNumber = trim($vatNumber);

        if ($countryId === '' || $vatNumber === '') {
            throw new LocalizedException(__('Country code and VAT number must be provided.'));
        }

        /** @var VatValidationInterface $validation */
        $validation = $this->vatValidationFactory->create();
        $validation->setCountryId($countryId)->setVatNumber($vatNumber);

        try {
            $response = $this->viesClient->check($countryId, $vatNumber);
        } catch (LocalizedException $e) {
            $this->logger->error('VAT validation failed: ' . $e->getMessage());
            throw $e;
        }

        $validation
            ->setIsValid($response['valid'])
            ->setName($response['name'] ?? null)
            ->setAddress($response['address'] ?? null)
            ->setRequestDate($response['request_date'] ?? null);

        $this->vatValidationRepository->save($validation);

        return $validation;
    }

    public function getByCountryAndNumber(string $countryId, string $vatNumber): ?VatValidationInterface
    {
        $collection = $this->vatValidationFactory->create()->getCollection();
        $collection->addFieldToFilter('country_id', $countryId);
        $collection->addFieldToFilter('vat_number', $vatNumber);
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();
        if ($item && $item->getEntityId()) {
            return $item;
        }

        return null;
    }
}
