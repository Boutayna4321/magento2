<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\Controller\Result\Redirect;

class MarkReceived extends AbstractRma
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
            $receivedQtys = (array) $this->getRequest()->getParam('items', []);
            $this->rmaService->receive($rmaId, $receivedQtys);
            $this->messageManager->addSuccessMessage(__('Return request #%1 marked as received.', $rmaId));
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This RMA no longer exists.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Unable to mark the RMA as received.'));
            $this->logger->error('RMA mark received failed: ' . $e->getMessage(), [
                'rma_id' => $rmaId,
                'exception' => $e,
            ]);
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
