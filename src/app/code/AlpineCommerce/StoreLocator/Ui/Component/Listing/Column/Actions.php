<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    private const URL_PATH_EDIT = 'storelocator/store/edit';
    private const URL_PATH_DELETE = 'storelocator/store/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
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
            $entityId = (int) $item['entity_id'];
            $item[$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['entity_id' => $entityId]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['entity_id' => $entityId]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Store'),
                        'message' => __('Are you sure you want to delete this store?'),
                    ],
                ],
            ];
        }

        return $dataSource;
    }
}
