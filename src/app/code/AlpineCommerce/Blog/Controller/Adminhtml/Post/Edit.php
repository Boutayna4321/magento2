<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Controller\Adminhtml\Post;

use AlpineCommerce\Blog\Api\BlogPostRepositoryInterface;
use AlpineCommerce\Blog\Model\BlogPostFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogPostRepositoryInterface $postRepository,
        Registry $coreRegistry,
        PageFactory $pageFactory,
        private readonly BlogPostFactory $postFactory
    ) {
        parent::__construct($context, $postRepository, $coreRegistry, $pageFactory);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('post_id');

        try {
            $model = $id
                ? $this->postRepository->getById($id)
                : $this->postFactory->create();
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
