<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ViesClient
{
    public const XML_PATH_ENABLED = 'alphacommerce_euvat/general/enabled';
    public const XML_PATH_WSDL = 'alphacommerce_euvat/general/wsdl';
    public const XML_PATH_TIMEOUT = 'alphacommerce_euvat/general/timeout';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SoapClientFactory $soapClientFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function check(string $countryCode, string $vatNumber): array
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED)) {
            throw new LocalizedException(__('VAT validation is disabled.'));
        }

        $wsdl = (string) $this->scopeConfig->getValue(self::XML_PATH_WSDL);
        $timeout = (int) $this->scopeConfig->getValue(self::XML_PATH_TIMEOUT);

        try {
            $client = $this->soapClientFactory->create($wsdl, $timeout > 0 ? $timeout : 10);
            $response = $client->checkVat([
                'countryCode' => strtoupper($countryCode),
                'vatNumber' => $vatNumber,
            ]);

            return [
                'valid' => (bool) ($response->valid ?? false),
                'name' => $response->name ?? null,
                'address' => $response->address ?? null,
                'request_id' => $response->requestIdentifier ?? null,
                'request_date' => $response->requestDate ?? null,
            ];
        } catch (\SoapFault $e) {
            $this->logger->error('VIES SOAP error: ' . $e->getMessage());
            throw new LocalizedException(__('VIES service is temporarily unavailable.'), $e);
        }
    }
}
