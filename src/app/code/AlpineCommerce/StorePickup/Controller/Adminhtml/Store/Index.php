<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Controller\Adminhtml\Store;

use Magento\Backend\Model\View\Result\Page;

class Index extends AbstractAction
{
    public function execute(): Page
    {
        $page = $this->initPage();
        $page->getConfig()->getTitle()->prepend(__('Store Pickup'));

        return $page;
    }
}
