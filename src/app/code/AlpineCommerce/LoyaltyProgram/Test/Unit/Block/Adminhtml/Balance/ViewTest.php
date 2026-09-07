<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Test\Unit\Block\Adminhtml\Balance;

use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Block\Adminhtml\Balance\View;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private View $block;
    private LoyaltyBalanceRepositoryInterface&MockObject $balanceRepository;
    private CustomerRepositoryInterface&MockObject $customerRepository;
    private Context&MockObject $context;

    protected function setUp(): void
    {
        $this->balanceRepository = $this->createMock(LoyaltyBalanceRepositoryInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->context = $this->createMock(Context::class);

        $request = $this->createMock(Http::class);
        $this->context->method('getRequest')->willReturn($request);

        $this->block = new View(
            $this->context,
            $this->balanceRepository,
            $this->customerRepository
        );
    }

    public function testGetCustomerIdReturnsRequestParam(): void
    {
        $request = $this->createMock(Http::class);
        $request->method('getParam')->with('customer_id')->willReturn(42);
        $this->context->method('getRequest')->willReturn($request);

        $block = new View($this->context, $this->balanceRepository, $this->customerRepository);

        $this->assertSame(42, $block->getCustomerId());
    }

    public function testGetBalanceReturnsZeroForInvalidCustomerId(): void
    {
        $request = $this->createMock(Http::class);
        $request->method('getParam')->with('customer_id')->willReturn(0);
        $this->context->method('getRequest')->willReturn($request);

        $block = new View($this->context, $this->balanceRepository, $this->customerRepository);

        $this->assertSame(0, $block->getBalance());
    }
}
