<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model\Rest;

use AlpineCommerce\Gdpr\Api\Data\GdprDeleteResultInterface;
use Magento\Framework\DataObject;

class GdprDeleteResult extends DataObject implements GdprDeleteResultInterface
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
