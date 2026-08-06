<?php
declare(strict_types=1);

namespace Cartware\Gdpr\Model\Rest;

use Cartware\Gdpr\Api\Data\GdprExportResultInterface;
use Magento\Framework\DataObject;

class GdprExportResult extends DataObject implements GdprExportResultInterface
{
    public function getCustomerId(): int
    {
        return (int) $this->getData('customer_id');
    }

    public function getPayload(): string
    {
        return (string) $this->getData('data');
    }

    public function getExportedAt(): string
    {
        return (string) $this->getData('exported_at');
    }
}
