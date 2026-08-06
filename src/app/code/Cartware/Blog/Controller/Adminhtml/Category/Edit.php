<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Adminhtml\Category;

use Cartware\Blog\Api\BlogCategoryRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Edit extends AbstractAction
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
            $model = $id
                ? $this->categoryRepository->getById($id)
                : $this->_objectManager->create(\Cartware\Blog\Model\BlogCategory::class);
            $this->coreRegistry->register('current_category', $model);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This blog category no longer exists.'));
            return $this->_redirect('*/*/');
        }

        $page = $this->initPage();
        $page->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Blog Category') : __('New Blog Category')
        );

        return $page;
    }
}
