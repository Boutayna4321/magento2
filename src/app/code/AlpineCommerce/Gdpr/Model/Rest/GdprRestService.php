<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model\Rest;

use AlpineCommerce\Gdpr\Api\ConsentManagementInterface;
use AlpineCommerce\Gdpr\Api\Data\GdprConsentResultInterface;
use AlpineCommerce\Gdpr\Api\Data\GdprDeleteResultInterface;
use AlpineCommerce\Gdpr\Api\Data\GdprExportResultInterface;
use AlpineCommerce\Gdpr\Api\GdprDeleteInterface;
use AlpineCommerce\Gdpr\Api\GdprExportInterface;
use AlpineCommerce\Gdpr\Api\GdprRestInterface;
use Magento\Authorization\Model\UserContextInterface;

class GdprRestService implements GdprRestInterface
{
    public function __construct(
        private readonly ConsentManagementInterface $consentManagement,
        private readonly GdprExportInterface $gdprExportService,
        private readonly GdprDeleteInterface $gdprDeleteService,
        private readonly UserContextInterface $userContext
    ) {
    }

    public function logConsent(string $consentType, bool $granted): GdprConsentResultInterface
    {
        $customerId = $this->userContext->getUserId();
        $success = $this->consentManagement->log(
            $customerId ? (int) $customerId : null,
            $consentType,
            $granted
        );

        return new GdprConsentResult([
            'success' => $success,
            'message' => $success
                ? (string) __('Consent recorded.')
                : (string) __('Unable to record the consent.'),
        ]);
    }

    public function exportData(): GdprExportResultInterface
    {
        $customerId = (int) $this->userContext->getUserId();
        $data = $this->gdprExportService->export($customerId);

        return new GdprExportResult([
            'customer_id' => $customerId,
            'data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'exported_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteData(): GdprDeleteResultInterface
    {
        $customerId = (int) $this->userContext->getUserId();
        $success = $this->gdprDeleteService->delete($customerId);

        return new GdprDeleteResult([
            'success' => $success,
            'message' => $success
                ? (string) __('Your personal data has been anonymized.')
                : (string) __('Unable to process the deletion request.'),
        ]);
    }
}
