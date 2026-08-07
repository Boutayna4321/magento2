<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Controller\Adminhtml\Store;

use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterface;
use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterfaceFactory;
use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Save extends AbstractAction
{
    private const TEXT_FIELDS = [
        StoreInfoInterface::SOURCE_CODE,
        StoreInfoInterface::NAME,
        StoreInfoInterface::STREET,
        StoreInfoInterface::CITY,
        StoreInfoInterface::REGION,
        StoreInfoInterface::POSTCODE,
        StoreInfoInterface::COUNTRY_ID,
        StoreInfoInterface::PHONE,
        StoreInfoInterface::OPENING_HOURS,
    ];

    public function __construct(
        Context $context,
        private readonly StoreInfoRepositoryInterface $storeInfoRepository,
        private readonly StoreInfoInterfaceFactory $storeInfoFactory,
        private readonly DataPersistorInterface $dataPersistor,
        protected readonly PageFactory $pageFactory
    ) {
        parent::__construct($context, $pageFactory);
    }

    public function execute()
    {
        $data = (array) $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('alphacommerce_pickup/store/index');
        }

        $id = (int) ($data['entity_id'] ?? 0);

        try {
            $store = $id ? $this->storeInfoRepository->getById($id) : $this->storeInfoFactory->create();

            if (isset($data['entity_id']) && (int) $data['entity_id'] !== $id) {
                $this->dataPersistor->set('alphacommerce_storepickup_store', $data);
                $this->messageManager->addErrorMessage(__('The store no longer exists.'));
                return $this->_redirect('*/*/edit', ['entity_id' => $id]);
            }

            foreach (self::TEXT_FIELDS as $field) {
                $value = trim((string) ($data[$field] ?? ''));
                $store->setData($field, $value === '' ? null : $value);
            }

            foreach ([StoreInfoInterface::LATITUDE, StoreInfoInterface::LONGITUDE] as $field) {
                $value = trim((string) ($data[$field] ?? ''));
                if ($value !== '' && !is_numeric($value)) {
                    throw new LocalizedException(__('%1 must be a valid number.', ucwords(str_replace('_', ' ', $field))));
                }
                $store->setData($field, $value === '' ? null : (float) $value);
            }

            $store->setData(StoreInfoInterface::IS_ACTIVE, !empty($data[StoreInfoInterface::IS_ACTIVE]) ? 1 : 0);
            $this->storeInfoRepository->save($store);

            $this->messageManager->addSuccessMessage(__('The store has been saved.'));
            $this->dataPersistor->clear('alphacommerce_storepickup_store');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the store: %1', $e->getMessage()));
            $this->dataPersistor->set('alphacommerce_storepickup_store', $data);
            return $this->_redirect('*/*/edit', ['entity_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['entity_id' => $store->getEntityId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
