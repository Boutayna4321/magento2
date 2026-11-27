<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Test\Unit\Block\Adminhtml\Balance;

use AlpineCommerce\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface;
use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Block\Adminhtml\Balance\View;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private LoyaltyBalanceRepositoryInterface&MockObject $balanceRepository;
    private CustomerRepositoryInterface&MockObject $customerRepository;
    private Context&MockObject $context;
    private Http&MockObject $request;

    protected function setUp(): void
    {
        $this->balanceRepository = $this->createMock(LoyaltyBalanceRepositoryInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        // The block extends Magento\Backend\Block\Template, so the context type is the Backend
        // one, not the frontend Magento\Framework\View\Element\Template\Context.
        $this->context = $this->createMock(Context::class);

        $this->request = $this->createMock(Http::class);
        $this->context->method('getRequest')->willReturn($this->request);
    }

    private function block(): View
    {
        return new View($this->context, $this->balanceRepository, $this->customerRepository);
    }

    public function testGetCustomerIdReturnsRequestParam(): void
    {
        // getCustomerId() calls getParam('customer_id', 0): the constraint must accept two args.
        $this->request->method('getParam')->with('customer_id', 0)->willReturn(42);

        $this->assertSame(42, $this->block()->getCustomerId());
    }

    public function testGetBalanceReturnsZeroForInvalidCustomerId(): void
    {
        $this->request->method('getParam')->with('customer_id', 0)->willReturn(0);
        $this->balanceRepository->expects($this->never())->method('getByCustomerId');

        $this->assertSame(0, $this->block()->getBalance());
    }

    public function testGetBalanceReturnsPointsForValidCustomer(): void
    {
        $this->request->method('getParam')->with('customer_id', 0)->willReturn(7);

        $balance = $this->createMock(LoyaltyBalanceInterface::class);
        $balance->method('getPoints')->willReturn(350);
        $this->balanceRepository->method('getByCustomerId')->with(7)->willReturn($balance);

        $this->assertSame(350, $this->block()->getBalance());
    }

    public function testGetCustomerReturnsNullWhenIdIsMissing(): void
    {
        $this->request->method('getParam')->with('customer_id', 0)->willReturn(0);
        $this->customerRepository->expects($this->never())->method('getById');

        $this->assertNull($this->block()->getCustomer());
    }

    public function testGetCustomerReturnsNullWhenCustomerDoesNotExist(): void
    {
        $this->request->method('getParam')->with('customer_id', 0)->willReturn(99);
        $this->customerRepository->method('getById')->with(99)
            ->willThrowException(new NoSuchEntityException(__('No such entity.')));

        $this->assertNull($this->block()->getCustomer());
    }
}
