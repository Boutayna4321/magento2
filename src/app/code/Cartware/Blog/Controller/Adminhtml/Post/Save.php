<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Adminhtml\Post;

use Cartware\Blog\Api\BlogPostRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Registry;

class Save extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogPostRepositoryInterface $postRepository,
        Registry $coreRegistry,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context, $postRepository, $coreRegistry);
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
                : $this->_objectManager->create(\Cartware\Blog\Model\BlogPost::class);

            if (isset($data['post_id']) && (int) $data['post_id'] !== $id) {
                $this->dataPersistor->set('cartware_blog_post', $data);
                $this->messageManager->addErrorMessage(__('The blog post was not found.'));
                return $this->_redirect('*/*/edit', ['post_id' => $id]);
            }

            if (isset($data['category_id']) && $data['category_id'] === '') {
                $data['category_id'] = null;
            }

            $model->setData($data);
            $this->postRepository->save($model);

            $this->messageManager->addSuccessMessage(__('The blog post has been saved.'));
            $this->dataPersistor->clear('cartware_blog_post');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the blog post: %1', $e->getMessage()));
            $this->dataPersistor->set('cartware_blog_post', $data);
            return $this->_redirect('*/*/edit', ['post_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['post_id' => $model->getId()]);
        }

        return $this->_redirect('*/*/index');
    }
}
