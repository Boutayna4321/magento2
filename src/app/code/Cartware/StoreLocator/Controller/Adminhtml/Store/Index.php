<?php
declare(strict_types=1);

namespace Cartware\StoreLocator\Controller\Adminhtml\Store;

use Magento\Framework\Controller\ResultFactory;

class Index extends AbstractStore
{
    public function execute()
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->prepend(__('Store Locator'));

        return $page;
    }
}
