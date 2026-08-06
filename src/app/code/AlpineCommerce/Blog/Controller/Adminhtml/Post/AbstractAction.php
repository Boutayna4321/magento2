<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Controller\Adminhtml\Post;

use AlpineCommerce\Blog\Api\BlogPostRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

abstract class AbstractAction extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_Blog::post';

    public function __construct(
        Context $context,
        protected readonly BlogPostRepositoryInterface $postRepository,
        protected readonly Registry $coreRegistry,
        protected readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    protected function initPage()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_Blog::menu');
        $page->addBreadcrumb(__('Content'), __('Content'));
        $page->addBreadcrumb(__('Blog'), __('Blog'));
        $page->addBreadcrumb(__('Posts'), __('Posts'));

        return $page;
    }
}
