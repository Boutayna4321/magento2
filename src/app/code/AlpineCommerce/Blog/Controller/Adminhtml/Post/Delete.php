<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Controller\Adminhtml\Post;

use AlpineCommerce\Blog\Api\BlogPostRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Delete extends AbstractAction
{
    public function __construct(
        Context $context,
        BlogPostRepositoryInterface $postRepository,
        Registry $coreRegistry,
        PageFactory $pageFactory
    ) {
        parent::__construct($context, $postRepository, $coreRegistry, $pageFactory);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('post_id');

        try {
            $this->postRepository->delete($this->postRepository->getById($id));
            $this->messageManager->addSuccessMessage(__('The blog post has been deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This blog post no longer exists.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete the blog post.'));
        }

        return $this->_redirect('*/*/index');
    }
}
