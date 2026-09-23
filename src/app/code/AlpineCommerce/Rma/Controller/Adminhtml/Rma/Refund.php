<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\Controller\Result\Redirect;

class Refund extends AbstractRma
{
    public function execute(): Redirect
    {
        $rmaId = (int) $this->getRequest()->getParam('id');

        if (!$rmaId) {
            $this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
            return $this->redirectToIndex();
        }

        if ($redirect = $this->requirePost()) {
            return $redirect;
        }

        try {
            $creditmemo = $this->rmaService->refund($rmaId);
            $this->messageManager->addSuccessMessage(
                __('Credit memo #%1 created and refund processed for RMA #%2.', $creditmemo->getIncrementId(), $rmaId)
            );
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This RMA no longer exists.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unable to process the refund.'));
            $this->logger->error('RMA refund failed: ' . $e->getMessage(), [
                'rma_id' => $rmaId,
                'exception' => $e,
            ]);
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
