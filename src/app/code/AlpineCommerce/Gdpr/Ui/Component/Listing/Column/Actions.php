<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    private const URL_PATH_EXPORT = 'alphacommerce_gdpr/consentlog/export';

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
            $customerId = (int) $item['customer_id'];
            $item[$this->getData('name')] = [
                'export' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_EXPORT, ['customer_id' => $customerId]),
                    'label' => __('Export'),
                    'confirm' => [
                        'title' => __('Export GDPR Data'),
                        'message' => __('Are you sure you want to export all personal data for this customer?'),
                    ],
                ],
            ];
        }

        return $dataSource;
    }
}
