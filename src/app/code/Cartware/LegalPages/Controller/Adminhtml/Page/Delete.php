<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Controller\Adminhtml\Page;

use Cartware\LegalPages\Api\LegalPageRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Delete extends AbstractAction
{
    public function __construct(
        Context $context,
        LegalPageRepositoryInterface $pageRepository,
        Registry $coreRegistry
    ) {
        parent::__construct($context, $pageRepository, $coreRegistry);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('page_id');

        try {
            $this->pageRepository->delete($this->pageRepository->getById($id));
            $this->messageManager->addSuccessMessage(__('The legal page has been deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This legal page no longer exists.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete the legal page.'));
        }

        return $this->_redirect('*/*/index');
    }
}
