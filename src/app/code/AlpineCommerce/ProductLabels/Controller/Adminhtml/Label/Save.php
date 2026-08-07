<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Controller\Adminhtml\Label;

use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use AlpineCommerce\ProductLabels\Model\ProductLabelFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    public function __construct(
        Context $context,
        private readonly ProductLabelRepositoryInterface $labelRepository,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ProductLabelFactory $labelFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $labelId = (int) ($data['entity_id'] ?? 0);

        try {
            $label = $labelId
                ? $this->labelRepository->getById($labelId)
                : $this->labelFactory->create();

            $label->setName($data['name'] ?? '')
                ->setCode($data['code'] ?? '')
                ->setColor($data['color'] ?? null)
                ->setTextColor($data['text_color'] ?? null)
                ->setPriority((int) ($data['priority'] ?? 0))
                ->setPosition($data['position'] ?? 'top-left')
                ->setIcon($data['icon'] ?? null)
                ->setStartDate($this->normalizeDate($data['start_date'] ?? null))
                ->setEndDate($this->normalizeDate($data['end_date'] ?? null))
                ->setIsActive(!empty($data['is_active']));

            $this->labelRepository->save($label);

            $productIds = $this->extractProductIds($data);
            $this->labelRepository->assignProductsToLabel((int) $label->getEntityId(), $productIds);

            $this->messageManager->addSuccessMessage(__('Product label saved successfully.'));
            $this->dataPersistor->clear('productlabels_label');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('productlabels_label', $data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $labelId]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while saving: %1', $e->getMessage()));
            $this->dataPersistor->set('productlabels_label', $data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $labelId]);
        }

        if (!empty($data['back'])) {
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => (int) $label->getEntityId()]);
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param array $data
     * @return int[]
     */
    private function extractProductIds(array $data): array
    {
        $productIds = $data['product_ids'] ?? [];
        if (is_string($productIds)) {
            $productIds = explode(',', $productIds);
        }

        return array_values(array_filter(array_map('intval', $productIds)));
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('AlpineCommerce_ProductLabels::labels_save');
    }
}
