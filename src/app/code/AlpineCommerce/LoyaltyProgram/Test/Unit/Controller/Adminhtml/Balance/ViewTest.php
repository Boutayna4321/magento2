<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Test\Unit\Controller\Adminhtml\Balance;

use AlpineCommerce\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface;
use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Controller\Adminhtml\Balance\View;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private Context&MockObject $context;
    private PageFactory&MockObject $resultPageFactory;
    private RedirectFactory&MockObject $redirectFactory;
    private ManagerInterface&MockObject $messageManager;
    private Http&MockObject $request;
    private LoyaltyBalanceRepositoryInterface&MockObject $balanceRepository;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        // The controller extends Magento\Backend\App\Action and calls setActiveMenu()/addBreadcrumb(),
        // so it needs the Backend page, not the frontend one.
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->redirectFactory = $this->createMock(RedirectFactory::class);
        $this->balanceRepository = $this->createMock(LoyaltyBalanceRepositoryInterface::class);

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
            $this->balanceRepository
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
        $this->balanceRepository->expects($this->never())->method('getByCustomerId');

        $this->assertSame($redirect, $this->createController()->execute());
    }

    public function testExecuteRedirectsWhenCustomerNotFound(): void
    {
        $this->request->method('getParam')->with('customer_id')->willReturn(999);

        $this->balanceRepository->method('getByCustomerId')->with(999)
            ->willThrowException(new NoSuchEntityException(__('Customer not found')));

        $redirect = $this->createMock(Redirect::class);
        $this->redirectFactory->method('create')->willReturn($redirect);
        $redirect->method('setPath')->with('loyalty/balance/index')->willReturnSelf();

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertSame($redirect, $this->createController()->execute());
    }

    public function testExecuteReturnsPageForValidCustomer(): void
    {
        $this->request->method('getParam')->with('customer_id')->willReturn(1);

        // getByCustomerId() returns an interface, so mock the interface rather than instantiating
        // the concrete model: LoyaltyBalance extends AbstractModel and needs constructor arguments.
        $this->balanceRepository->method('getByCustomerId')->with(1)
            ->willReturn($this->createMock(LoyaltyBalanceInterface::class));

        $resultPage = $this->createMock(Page::class);
        $resultPage->method('setActiveMenu')->willReturnSelf();
        $resultPage->method('addBreadcrumb')->willReturnSelf();
        $config = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $title = $this->createMock(\Magento\Framework\View\Page\Title::class);
        $title->method('prepend')->willReturnSelf();
        $config->method('getTitle')->willReturn($title);
        $resultPage->method('getConfig')->willReturn($config);
        $this->resultPageFactory->method('create')->willReturn($resultPage);

        $this->assertInstanceOf(Page::class, $this->createController()->execute());
    }
}
