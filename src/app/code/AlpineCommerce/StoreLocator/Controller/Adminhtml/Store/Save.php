<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Controller\Adminhtml\Store;

use AlpineCommerce\StoreLocator\Api\Data\StoreInterface;
use AlpineCommerce\StoreLocator\Api\Data\StoreInterfaceFactory;
use AlpineCommerce\StoreLocator\Api\StoreRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Save extends AbstractAction
{
    private const TEXT_FIELDS = [
        StoreInterface::NAME,
        StoreInterface::STREET,
        StoreInterface::CITY,
        StoreInterface::REGION,
        StoreInterface::POSTCODE,
        StoreInterface::COUNTRY_ID,
        StoreInterface::PHONE,
        StoreInterface::OPENING_HOURS,
    ];

    public function __construct(
        Context $context,
        private readonly StoreRepositoryInterface $storeRepository,
        private readonly StoreInterfaceFactory $storeFactory,
        private readonly DataPersistorInterface $dataPersistor,
        PageFactory $pageFactory
    ) {
        parent::__construct($context, $pageFactory);
    }

    public function execute()
    {
        $data = (array) $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('storelocator/store/index');
        }

        $id = (int) ($data['entity_id'] ?? 0);

        try {
            $store = $id ? $this->storeRepository->getById($id) : $this->storeFactory->create();

            if (isset($data['entity_id']) && (int) $data['entity_id'] !== $id) {
                $this->dataPersistor->set('alphacommerce_storelocator_store', $data);
                $this->messageManager->addErrorMessage(__('The store no longer exists.'));
                return $this->_redirect('*/*/edit', ['entity_id' => $id]);
            }

            foreach (self::TEXT_FIELDS as $field) {
                $value = trim((string) ($data[$field] ?? ''));
                $store->setData($field, $value === '' ? null : $value);
            }

            foreach ([StoreInterface::LATITUDE, StoreInterface::LONGITUDE] as $field) {
                $value = trim((string) ($data[$field] ?? ''));
                if ($value !== '' && !is_numeric($value)) {
                    throw new LocalizedException(__('%1 must be a valid number.', ucwords(str_replace('_', ' ', $field))));
                }
                $store->setData($field, $value === '' ? null : (float) $value);
            }

            $store->setData(StoreInterface::IS_ACTIVE, !empty($data[StoreInterface::IS_ACTIVE]) ? 1 : 0);
            $this->storeRepository->save($store);

            $this->messageManager->addSuccessMessage(__('The store has been saved.'));
            $this->dataPersistor->clear('alphacommerce_storelocator_store');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the store: %1', $e->getMessage()));
            $this->dataPersistor->set('alphacommerce_storelocator_store', $data);
            return $this->_redirect('*/*/edit', ['entity_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['entity_id' => $store->getEntityId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
