<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Verification extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $value = $item['is_verified_purchase'] ?? 0;
            $item[$this->getData('name')] = $value
                ? __('Yes')
                : __('No');
        }

        return $dataSource;
    }
}
