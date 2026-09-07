<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Test\Unit\Controller\Adminhtml\Balance;

use AlpineCommerce\LoyaltyProgram\Controller\Adminhtml\Balance\View;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private Context $context;
    private PageFactory&MockObject $resultPageFactory;
    private RedirectFactory&MockObject $redirectFactory;
    private ManagerInterface&MockObject $messageManager;
    private Http&MockObject $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->redirectFactory = $this->createMock(RedirectFactory::class);

        $this->context = $this->createMock(Context::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->context->method('getMessageManager')->willReturn($this->messageManager);
    }

    private function createController(): View
    {
        return new View(
            $this->context,
            $this->resultPageFactory,
            $this->redirectFactory,
            $this->createMock(\AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface::class)
        );
    }

    public function testAdminResourceConstant(): void
    {
        $this->assertSame('AlpineCommerce_LoyaltyProgram::balance', View::ADMIN_RESOURCE);
    }

    public function testExecuteRedirectsWhenCustomerIdMissing(): void
    {
        $this->request->method('getParam')->with('customer_id')->willReturn(0);

        $redirect = $this->createMock(Redirect::class);
        $this->redirectFactory->method('create')->willReturn($redirect);
        $redirect->method('setPath')->with('loyalty/balance/index')->willReturnSelf();

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $controller = $this->createController();
        $result = $controller->execute();

        $this->assertSame($redirect, $result);
    }

    public function testExecuteRedirectsWhenCustomerNotFound(): void
    {
        $this->request->method('getParam')->with('customer_id')->willReturn(999);

        $balanceRepository = $this->createMock(\AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface::class);
        $balanceRepository->method('getByCustomerId')->with(999)
            ->willThrowException(new NoSuchEntityException(__('Customer not found')));

        $redirect = $this->createMock(Redirect::class);
        $this->redirectFactory->method('create')->willReturn($redirect);
        $redirect->method('setPath')->with('loyalty/balance/index')->willReturnSelf();

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $controller = new View(
            $this->context,
            $this->resultPageFactory,
            $this->redirectFactory,
            $balanceRepository
        );
        $result = $controller->execute();

        $this->assertSame($redirect, $result);
    }

    public function testExecuteReturnsPageForValidCustomer(): void
    {
        $this->request->method('getParam')->with('customer_id')->willReturn(1);

        $balanceRepository = $this->createMock(\AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface::class);
        $balanceRepository->method('getByCustomerId')->with(1)
            ->willReturn(new \AlpineCommerce\LoyaltyProgram\Model\LoyaltyBalance());

        $resultPage = $this->createMock(Page::class);

        $this->resultPageFactory->method('create')->willReturn($resultPage);

        $controller = new View(
            $this->context,
            $this->resultPageFactory,
            $this->redirectFactory,
            $balanceRepository
        );
        $result = $controller->execute();

        $this->assertInstanceOf(Page::class, $result);
    }
}
