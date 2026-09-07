<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints;

use Magento\Framework\ObjectManagerInterface;

class GridCollectionFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @var string
     */
    private string $instanceName;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        string $instanceName = '\\AlpineCommerce\\LoyaltyProgram\\Model\\ResourceModel\\LoyaltyOrderPoints\\GridCollection'
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * @param array $data
     * @return GridCollection
     */
    public function create(array $data = []): GridCollection
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
