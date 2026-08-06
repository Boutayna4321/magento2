<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Controller\Adminhtml\Category;

use AlpineCommerce\Blog\Api\BlogCategoryRepositoryInterface;
use AlpineCommerce\Blog\Model\BlogCategoryFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogCategoryRepositoryInterface $categoryRepository,
        Registry $coreRegistry,
        PageFactory $pageFactory,
        private readonly BlogCategoryFactory $categoryFactory
    ) {
        parent::__construct($context, $categoryRepository, $coreRegistry, $pageFactory);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('category_id');

        try {
            $model = $id
                ? $this->categoryRepository->getById($id)
                : $this->categoryFactory->create();
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
