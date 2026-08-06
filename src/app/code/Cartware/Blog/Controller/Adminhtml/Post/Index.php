<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;

class Index extends AbstractAction
{
    public function execute(): Page
    {
        $page = $this->initPage();
        $page->getConfig()->getTitle()->prepend(__('Blog Posts'));

        return $page;
    }
}
