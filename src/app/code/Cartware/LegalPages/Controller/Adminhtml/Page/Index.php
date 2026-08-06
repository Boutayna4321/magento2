<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Controller\Adminhtml\Page;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;

class Index extends AbstractAction
{
    public function execute(): Page
    {
        $page = $this->initPage();
        $page->getConfig()->getTitle()->prepend(__('Legal Pages'));

        return $page;
    }
}
