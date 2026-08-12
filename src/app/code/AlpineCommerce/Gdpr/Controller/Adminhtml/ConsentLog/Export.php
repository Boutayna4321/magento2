<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Controller\Adminhtml\ConsentLog;

use AlpineCommerce\Gdpr\Api\GdprExportInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Export extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_Gdpr::export';

    public function __construct(
        Context $context,
        private readonly GdprExportInterface $gdprExportService
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $customerId = (int) $this->getRequest()->getParam('customer_id');

        try {
            $data = $this->gdprExportService->export($customerId);
        } catch (NoSuchEntityException $e) {
            $data = ['error' => $e->getMessage()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)
            ->setData($data);
    }
}
