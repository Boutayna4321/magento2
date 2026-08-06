<?php
declare(strict_types=1);

namespace Cartware\StoreLocator\Controller\Adminhtml\Store;

use Cartware\StorePickup\Api\Data\StoreInfoInterface;
use Cartware\StorePickup\Api\Data\StoreInfoInterfaceFactory;
use Cartware\StorePickup\Api\StoreInfoRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends AbstractStore implements HttpPostActionInterface
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
        \Magento\Backend\App\Action\Context $context,
        private readonly StoreInfoRepositoryInterface $storeRepository,
        private readonly StoreInfoInterfaceFactory $storeFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = (array)$this->getRequest()->getPostValue();
        $id = (int)($data['entity_id'] ?? 0);

        try {
            $store = $id ? $this->storeRepository->getById($id) : $this->storeFactory->create();
            $sourceCode = trim((string)($data[StoreInfoInterface::SOURCE_CODE] ?? ''));
            $name = trim((string)($data[StoreInfoInterface::NAME] ?? ''));

            if ($sourceCode === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $sourceCode)) {
                throw new LocalizedException(__('Source Code must contain only letters, numbers, underscores, or hyphens.'));
            }
            if ($name === '') {
                throw new LocalizedException(__('Store Name is required.'));
            }

            foreach (self::TEXT_FIELDS as $field) {
                $value = trim((string)($data[$field] ?? ''));
                $store->setData($field, $value === '' ? null : $value);
            }
            foreach ([StoreInfoInterface::LATITUDE, StoreInfoInterface::LONGITUDE] as $field) {
                $value = trim((string)($data[$field] ?? ''));
                if ($value !== '' && !is_numeric($value)) {
                    throw new LocalizedException(__('%1 must be a valid number.', ucwords(str_replace('_', ' ', $field))));
                }
                $store->setData($field, $value === '' ? null : (float)$value);
            }
            $store->setData(StoreInfoInterface::IS_ACTIVE, !empty($data[StoreInfoInterface::IS_ACTIVE]) ? 1 : 0);
            $this->storeRepository->save($store);
            $this->messageManager->addSuccessMessage(__('The store has been saved.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('The store could not be saved.'));
        }

        $this->_getSession()->setFormData($data);
        return $this->resultRedirectFactory->create()->setPath('*/*/edit', ['id' => $id]);
    }
}
