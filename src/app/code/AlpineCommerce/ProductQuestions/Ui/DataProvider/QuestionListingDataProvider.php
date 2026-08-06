<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Ui\DataProvider;

use AlpineCommerce\ProductQuestions\Model\ResourceModel\Question\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class QuestionListingDataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }
}
