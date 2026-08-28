<?php
/**
 * Copyright (c) AlpineCommerce. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AlpineCommerce\Rma\Controller\Adminhtml\Rma;

use AlpineCommerce\Rma\Api\Data\RmaInterface;
use AlpineCommerce\Rma\Api\RmaRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

abstract class AbstractRma extends Action
{
    public function __construct(
        Context $context,
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('AlpineCommerce_Rma::rma');
    }

    protected function changeStatus(int $rmaId, string $status, string $successMessage): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $rma = $this->rmaRepository->getById($rmaId);
            $rma->setStatus($status);
            $this->rmaRepository->save($rma);

            $this->messageManager->addSuccessMessage($successMessage);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This RMA no longer exists.'));
            $this->logger->warning('RMA status change failed: entity not found.', [
                'rma_id' => $rmaId,
                'exception' => $e,
            ]);
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unable to update the RMA status.'));
            $this->logger->error('RMA status change failed: ' . $e->getMessage(), [
                'rma_id' => $rmaId,
                'exception' => $e,
            ]);
        }

        return $resultRedirect->setPath('*/*/index');
    }

    protected function loadRmaIdFromRequest(): int
    {
        return (int) $this->getRequest()->getParam('id');
    }

    protected function invalidIdRedirect(): Redirect
    {
        $this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
