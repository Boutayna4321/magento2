<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model;

use AlpineCommerce\Gdpr\Api\ConsentLogRepositoryInterface;
use AlpineCommerce\Gdpr\Api\ConsentManagementInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Exception\CouldNotSaveException;

class ConsentManagement implements ConsentManagementInterface
{
    public function __construct(
        private readonly ConsentLogRepositoryInterface $consentLogRepository,
        private readonly ConsentLogFactory $consentLogFactory,
        private readonly RemoteAddress $remoteAddress,
        private readonly Header $httpHeader
    ) {
    }

    /**
     * @param int|null $customerId
     * @param string $consentType
     * @param bool $granted
     * @return bool
     */
    public function log(?int $customerId, string $consentType, bool $granted): bool
    {
        /** @var \AlpineCommerce\Gdpr\Api\Data\ConsentLogInterface $log */
        $log = $this->consentLogFactory->create();
        $log->setCustomerId($customerId)
            ->setConsentType($consentType)
            ->setIsGranted($granted)
            ->setIpAddress($this->remoteAddress->getRemoteAddress())
            ->setUserAgent(substr((string) $this->httpHeader->getHttpUserAgent(), 0, 255));

        try {
            $this->consentLogRepository->save($log);
        } catch (CouldNotSaveException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param int $customerId
     * @return array<string, mixed>
     */
    public function getHistory(int $customerId): array
    {
        $items = [];
        $collection = $this->consentLogFactory->create()->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC');

        foreach ($collection as $log) {
            $items[] = [
                'consent_type' => $log->getConsentType(),
                'granted' => $log->isGranted(),
                'created_at' => $log->getCreatedAt(),
                'ip_address' => $log->getIpAddress(),
            ];
        }

        return $items;
    }
}
