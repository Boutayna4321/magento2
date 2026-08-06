<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Controller\Adminhtml\Page;

use AlpineCommerce\LegalPages\Api\LegalPageRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Delete extends AbstractAction
{
    public function __construct(
        Context $context,
        LegalPageRepositoryInterface $pageRepository,
        Registry $coreRegistry,
        PageFactory $pageFactory
    ) {
        parent::__construct($context, $pageRepository, $coreRegistry, $pageFactory);
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
