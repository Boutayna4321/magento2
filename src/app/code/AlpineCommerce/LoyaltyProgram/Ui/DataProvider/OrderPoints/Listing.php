<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Ui\DataProvider\OrderPoints;

use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints\GridCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class Listing extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var int|null
     */
    private $customerId = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param GridCollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        GridCollectionFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $customerId = (int) $this->request->getParam('customer_id', 0);

        if ($customerId > 0 && $this->customerId !== $customerId) {
            $this->customerId = $customerId;
            $this->getCollection()->clear();
            $this->getCollection()->addCustomerFilter($customerId);
        }

        return parent::getData();
    }
}
