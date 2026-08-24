<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model;

use Magento\Framework\ObjectManagerInterface;

class RmaFactory
{
    private ObjectManagerInterface $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Rma
    {
        return $this->objectManager->create(Rma::class);
    }
}
