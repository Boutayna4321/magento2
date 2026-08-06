<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model\Rest;

use AlpineCommerce\Gdpr\Api\Data\GdprConsentResultInterface;
use Magento\Framework\DataObject;

class GdprConsentResult extends DataObject implements GdprConsentResultInterface
{
    public function getSuccess(): bool
    {
        return (bool) $this->getData('success');
    }

    public function getMessage(): string
    {
        return (string) $this->getData('message');
    }
}
