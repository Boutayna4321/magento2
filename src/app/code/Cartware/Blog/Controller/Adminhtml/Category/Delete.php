<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Adminhtml\Category;

use Cartware\Blog\Api\BlogCategoryRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Delete extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogCategoryRepositoryInterface $categoryRepository,
        Registry $coreRegistry
    ) {
        parent::__construct($context, $categoryRepository, $coreRegistry);
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
