<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Adminhtml\Category;

use Cartware\Blog\Api\BlogCategoryRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;

class Save extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogCategoryRepositoryInterface $categoryRepository,
        Registry $coreRegistry,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context, $categoryRepository, $coreRegistry);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('blog/category/index');
        }

        $id = (int) $this->getRequest()->getParam('category_id');

        try {
            $model = $id
                ? $this->categoryRepository->getById($id)
                : $this->_objectManager->create(\Cartware\Blog\Model\BlogCategory::class);

            if (isset($data['category_id']) && (int) $data['category_id'] !== $id) {
                $this->dataPersistor->set('cartware_blog_category', $data);
                $this->messageManager->addErrorMessage(__('The blog category was not found.'));
                return $this->_redirect('*/*/edit', ['category_id' => $id]);
            }

            $model->setData($data);
            $this->categoryRepository->save($model);

            $this->messageManager->addSuccessMessage(__('The blog category has been saved.'));
            $this->dataPersistor->clear('cartware_blog_category');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the blog category: %1', $e->getMessage()));
            $this->dataPersistor->set('cartware_blog_category', $data);
            return $this->_redirect('*/*/edit', ['category_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['category_id' => $model->getId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
