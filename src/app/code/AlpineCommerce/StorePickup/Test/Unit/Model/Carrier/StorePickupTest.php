<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Test\Unit\Model\Carrier;

use AlpineCommerce\StorePickup\Api\StoreAvailabilityInterface;
use AlpineCommerce\StorePickup\Model\Carrier\StorePickup;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Store pickup must only be offered when at least one active store has every cart
 * item in stock; otherwise the customer could pick "Store Pickup" with no store.
 */
class StorePickupTest extends TestCase
{
    private StoreAvailabilityInterface&MockObject $availability;

    protected function setUp(): void
    {
        $this->availability = $this->createMock(StoreAvailabilityInterface::class);
    }

    private function carrier(): StorePickup
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn(true);
        $scopeConfig->method('getValue')->willReturn('Store Pickup');

        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')->willReturnCallback(
            fn () => $this->getMockBuilder(Result::class)->disableOriginalConstructor()
                ->onlyMethods(['append'])->getMock()
        );
        $methodFactory = $this->createMock(MethodFactory::class);
        $methodFactory->method('create')->willReturnCallback(
            fn () => $this->getMockBuilder(Method::class)->disableOriginalConstructor()
                ->addMethods(['setCarrier', 'setCarrierTitle', 'setMethod', 'setMethodTitle', 'setCost'])
                ->onlyMethods(['setPrice'])->getMock()
        );

        return new StorePickup(
            $scopeConfig,
            $this->createMock(ErrorFactory::class),
            $this->createMock(LoggerInterface::class),
            $resultFactory,
            $methodFactory,
            $this->availability
        );
    }

    private function request(array $items): RateRequest
    {
        $quoteItems = [];
        foreach ($items as $sku => [$qty, $parent]) {
            $item = $this->getMockBuilder(Item::class)->disableOriginalConstructor()
                ->onlyMethods(['getSku', 'getQty', 'getParentItem'])->getMock();
            $item->method('getSku')->willReturn($sku);
            $item->method('getQty')->willReturn($qty);
            $item->method('getParentItem')->willReturn($parent ? $this->createMock(Item::class) : null);
            $quoteItems[] = $item;
        }

        $request = $this->getMockBuilder(RateRequest::class)->disableOriginalConstructor()
            ->addMethods(['getAllItems'])->getMock();
        $request->method('getAllItems')->willReturn($quoteItems);

        return $request;
    }

    public function testNotOfferedWhenNoStoreHasTheItems(): void
    {
        $this->availability->expects($this->once())->method('getAvailableStores')
            ->with(['24-MB01' => 1.0])->willReturn([]);

        $this->assertFalse($this->carrier()->collectRates($this->request(['24-MB01' => [1, false]])));
    }

    public function testOfferedWhenAStoreHasTheItems(): void
    {
        $this->availability->method('getAvailableStores')->willReturn([['source_code' => 'casa']]);

        $this->assertInstanceOf(Result::class, $this->carrier()->collectRates($this->request(['24-MB01' => [2, false]])));
    }

    public function testChildItemsOfConfigurableProductsAreNotCountedTwice(): void
    {
        $this->availability->expects($this->once())->method('getAvailableStores')
            ->with(['MS-T-M' => 1.0])->willReturn([]);

        $this->carrier()->collectRates($this->request(['MS-T-M' => [1, false], 'MS-T-M-child' => [1, true]]));
    }
}
