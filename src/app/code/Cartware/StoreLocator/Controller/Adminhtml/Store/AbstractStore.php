<?php
declare(strict_types=1);

namespace Cartware\StoreLocator\Controller\Adminhtml\Store;

use Magento\Backend\App\Action;

abstract class AbstractStore extends Action
{
    public const ADMIN_RESOURCE = 'Cartware_StoreLocator::stores';
}
