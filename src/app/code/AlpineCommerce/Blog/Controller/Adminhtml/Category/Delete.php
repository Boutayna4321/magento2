<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Controller\Adminhtml\Category;

use AlpineCommerce\Blog\Api\BlogCategoryRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Delete extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogCategoryRepositoryInterface $categoryRepository,
        Registry $coreRegistry,
        PageFactory $pageFactory
    ) {
        parent::__construct($context, $categoryRepository, $coreRegistry, $pageFactory);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('category_id');

        try {
            $this->categoryRepository->delete($this->categoryRepository->getById($id));
            $this->messageManager->addSuccessMessage(__('The blog category has been deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This blog category no longer exists.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete the blog category.'));
        }

        return $this->_redirect('*/*/index');
    }
}
