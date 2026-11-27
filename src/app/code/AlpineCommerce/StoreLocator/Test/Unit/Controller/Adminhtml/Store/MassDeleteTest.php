<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Test\Unit\Controller\Adminhtml\Store;

use AlpineCommerce\StoreLocator\Api\StoreRepositoryInterface;
use AlpineCommerce\StoreLocator\Controller\Adminhtml\Store\MassDelete;
use AlpineCommerce\StoreLocator\Model\ResourceModel\Store\Collection;
use AlpineCommerce\StoreLocator\Model\ResourceModel\Store\CollectionFactory;
use AlpineCommerce\StoreLocator\Model\Store;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Ui\Component\MassAction\Filter;
use PHPUnit\Framework\TestCase;

/**
 * The grid's "Delete" mass action used to post to "mui/massAction", a route that does
 * not exist. MassDelete deletes every selected store through the repository.
 */
class MassDeleteTest extends TestCase
{
    public function testDeletesEverySelectedStoreAndRedirectsToTheGrid(): void
    {
        $first = $this->createMock(Store::class);
        $second = $this->createMock(Store::class);

        $collection = $this->createMock(Collection::class);
        $collection->method('getItems')->willReturn([$first, $second]);

        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $filter = $this->createMock(Filter::class);
        $filter->method('getCollection')->with($collection)->willReturn($collection);

        $repository = $this->createMock(StoreRepositoryInterface::class);
        $deleted = [];
        $repository->expects($this->exactly(2))->method('delete')
            ->willReturnCallback(function ($store) use (&$deleted) {
                $deleted[] = $store;
                return true;
            });

        $messages = $this->createMock(ManagerInterface::class);
        $messages->expects($this->once())->method('addSuccessMessage');

        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->once())->method('setPath')->with('*/*/')->willReturnSelf();
        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')->with(ResultFactory::TYPE_REDIRECT)->willReturn($redirect);

        $objectManager = new ObjectManager($this);
        $context = $objectManager->getObject(\Magento\Backend\App\Action\Context::class, [
            'messageManager' => $messages,
            'resultFactory' => $resultFactory,
        ]);

        $controller = new MassDelete($context, $filter, $factory, $repository);

        $this->assertSame($redirect, $controller->execute());
        $this->assertSame([$first, $second], $deleted);
    }
}
