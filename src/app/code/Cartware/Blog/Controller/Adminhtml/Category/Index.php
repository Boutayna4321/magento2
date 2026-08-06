<?php
declare(strict_types=1);

namespace Cartware\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;

class Index extends AbstractAction
{
    public function execute(): Page
    {
        $page = $this->initPage();
        $page->getConfig()->getTitle()->prepend(__('Blog Categories'));

        return $page;
    }
}
