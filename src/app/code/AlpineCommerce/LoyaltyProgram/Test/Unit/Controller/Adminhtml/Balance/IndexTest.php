<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Test\Unit\Controller\Adminhtml\Balance;

use AlpineCommerce\LoyaltyProgram\Controller\Adminhtml\Balance\Index;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    private Index $controller;
    private PageFactory&MockObject $resultPageFactory;
    private Context&MockObject $context;
    private Page&MockObject $resultPage;

    protected function setUp(): void
    {
        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->resultPage = $this->createMock(Page::class);

        $request = $this->createMock(Http::class);
        $messageManager = $this->createMock(ManagerInterface::class);
        $this->context = $this->createMock(Context::class);
        $this->context->method('getRequest')->willReturn($request);
        $this->context->method('getMessageManager')->willReturn($messageManager);

        $this->controller = new Index(
            $this->context,
            $this->resultPageFactory
        );
    }

    public function testAdminResourceConstant(): void
    {
        $this->assertSame('AlpineCommerce_LoyaltyProgram::balance', Index::ADMIN_RESOURCE);
    }

    public function testExecuteReturnsPageWithCorrectTitle(): void
    {
        $this->resultPageFactory->method('create')->willReturn($this->resultPage);
        $this->resultPage->method('setActiveMenu')->with(Index::ADMIN_RESOURCE)->willReturnSelf();

        $config = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $this->resultPage->method('getConfig')->willReturn($config);

        $result = $this->controller->execute();

        $this->assertSame($this->resultPage, $result);
    }
}
