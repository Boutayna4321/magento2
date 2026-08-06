<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Adminhtml\Post;

use Cartware\Blog\Api\BlogPostRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Edit extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogPostRepositoryInterface $postRepository,
        Registry $coreRegistry
    ) {
        parent::__construct($context, $postRepository, $coreRegistry);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('post_id');

        try {
            $model = $id
                ? $this->postRepository->getById($id)
                : $this->_objectManager->create(\Cartware\Blog\Model\BlogPost::class);
            $this->coreRegistry->register('current_post', $model);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This blog post no longer exists.'));
            return $this->_redirect('*/*/');
        }

        $page = $this->initPage();
        $page->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Blog Post') : __('New Blog Post')
        );

        return $page;
    }
}
