<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Adminhtml\Post;

use Cartware\Blog\Api\BlogPostRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

abstract class AbstractAction extends Action
{
    public const ADMIN_RESOURCE = 'Cartware_Blog::post';

    public function __construct(
        Context $context,
        protected readonly BlogPostRepositoryInterface $postRepository,
        protected readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    protected function initPage()
    {
        $page = $this->_objectManager->create(\Magento\Framework\View\Result\PageFactory::class)->create();
        $page->setActiveMenu('Cartware_Blog::menu');
        $page->addBreadcrumb(__('Content'), __('Content'));
        $page->addBreadcrumb(__('Blog'), __('Blog'));
        $page->addBreadcrumb(__('Posts'), __('Posts'));

        return $page;
    }
}
