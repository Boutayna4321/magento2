<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Controller\Adminhtml\Post;

use AlpineCommerce\Blog\Api\BlogPostRepositoryInterface;
use AlpineCommerce\Blog\Model\BlogPostFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Save extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogPostRepositoryInterface $postRepository,
        Registry $coreRegistry,
        PageFactory $pageFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly BlogPostFactory $postFactory
    ) {
        parent::__construct($context, $postRepository, $coreRegistry, $pageFactory);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('blog/post/index');
        }

        $id = (int) $this->getRequest()->getParam('post_id');

        try {
            $model = $id
                ? $this->postRepository->getById($id)
                : $this->postFactory->create();

            if (isset($data['post_id']) && (int) $data['post_id'] !== $id) {
                $this->dataPersistor->set('alphacommerce_blog_post', $data);
                $this->messageManager->addErrorMessage(__('The blog post was not found.'));
                return $this->_redirect('*/*/edit', ['post_id' => $id]);
            }

            if (isset($data['category_id']) && $data['category_id'] === '') {
                $data['category_id'] = null;
            }

            $model->setData($data);
            $this->postRepository->save($model);

            $this->messageManager->addSuccessMessage(__('The blog post has been saved.'));
            $this->dataPersistor->clear('alphacommerce_blog_post');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the blog post: %1', $e->getMessage()));
            $this->dataPersistor->set('alphacommerce_blog_post', $data);
            return $this->_redirect('*/*/edit', ['post_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['post_id' => $model->getId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
