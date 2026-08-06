<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface;
use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use AlpineCommerce\ProductLabels\Model\ProductLabelFactory;
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
            return $resultRedirect->setPath("*/*/");
        }

        $labelId = (int) ($data["entity_id"] ?? 0);

        try {
            $label = $labelId
                ? $this->labelRepository->getById($labelId)
                : $this->labelFactory->create();
            $label->setName($data["name"] ?? "")
                ->setCode($data["code"] ?? "")
                ->setColor($data["color"] ?? null)
                ->setTextColor($data["text_color"] ?? null)
                ->setPriority((int) ($data["priority"] ?? 0))
                ->setPosition($data["position"] ?? "top-left")
                ->setIcon($data["icon"] ?? null)
                ->setStartDate($data["start_date"] ?? null)
                ->setEndDate($data["end_date"] ?? null)
                ->setIsActive(!empty($data["is_active"]))
                ->setProductIds($data["product_ids"] ?? []);
            $this->labelRepository->save($label);

            $productIds = $data["product_ids"] ?? [];
            if (is_string($productIds)) {
                $productIds = explode(",", $productIds);
            }
            $this->labelRepository->assignLabelsToProduct(
                (int) $label->getEntityId(),
                array_map("intval", $productIds)
            );

            $this->messageManager->addSuccessMessage(__("Product label saved successfully."));
            $this->dataPersistor->clear("productlabels_label");
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set("productlabels_label", $data);
            return $resultRedirect->setPath("*/*/edit", ["entity_id" => $labelId]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("An error occurred while saving: %1", $e->getMessage()));
            $this->dataPersistor->set("productlabels_label", $data);
            return $resultRedirect->setPath("*/*/edit", ["entity_id" => $labelId]);
        }

        return $resultRedirect->setPath("*/*/");
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed("AlpineCommerce_ProductLabels::labels_save");
    }
}
