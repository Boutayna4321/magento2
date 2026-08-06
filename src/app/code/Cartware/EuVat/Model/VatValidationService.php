<?php
declare(strict_types=1);

namespace Cartware\EuVat\Model;

use Cartware\EuVat\Api\Data\VatValidationInterface;
use Cartware\EuVat\Api\VatValidationInterface as VatValidationServiceInterface;
use Cartware\EuVat\Api\VatValidationRepositoryInterface;
use Cartware\EuVat\Model\VatValidationFactory as VatValidationModelFactory;
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

    /**
     * @param string $countryCode
     * @param string $vatNumber
     * @return VatValidationInterface
     */
    public function validate(string $countryCode, string $vatNumber): VatValidationInterface
    {
        $countryCode = strtoupper(trim($countryCode));
        $vatNumber = trim($vatNumber);

        if ($countryCode === '' || $vatNumber === '') {
            throw new LocalizedException(__('Country code and VAT number must be provided.'));
        }

        /** @var VatValidationInterface $validation */
        $validation = $this->vatValidationFactory->create();
        $validation->setCountryCode($countryCode)->setVatNumber($vatNumber);

        try {
            $response = $this->viesClient->check($countryCode, $vatNumber);
        } catch (LocalizedException $e) {
            $this->logger->error('VAT validation failed: ' . $e->getMessage());
            throw $e;
        }

        $validation
            ->setIsValid($response['valid'])
            ->setName($response['name'] ?? null)
            ->setAddress($response['address'] ?? null)
            ->setRequestId($response['request_id'] ?? null)
            ->setRequestDate($response['request_date'] ?? null);

        $this->vatValidationRepository->save($validation);

        return $validation;
    }
}
