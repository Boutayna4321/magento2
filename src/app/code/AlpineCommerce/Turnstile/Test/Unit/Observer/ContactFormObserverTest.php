<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Observer;

use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Observer\ContactFormObserver;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContactFormObserverTest extends TestCase
{
    private FormGuard&MockObject $formGuard;
    private DataPersistorInterface&MockObject $dataPersistor;
    private RequestInterface&MockObject $request;
    private HttpInterface&MockObject $response;
    private ContactFormObserver $observer;

    protected function setUp(): void
    {
        $this->formGuard = $this->createMock(FormGuard::class);
        $this->dataPersistor = $this->createMock(DataPersistorInterface::class);
        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->with('contact/index')->willReturn('https://shop.test/contact/index/');
        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(HttpInterface::class);

        $this->observer = new ContactFormObserver($this->formGuard, $this->dataPersistor, $url);
    }

    private function eventFor(mixed $action): Observer
    {
        return new Observer(['event' => new Event(['controller_action' => $action])]);
    }

    private function action(): AbstractAction&MockObject
    {
        $action = $this->createMock(AbstractAction::class);
        $action->method('getRequest')->willReturn($this->request);
        $action->method('getResponse')->willReturn($this->response);

        return $action;
    }

    public function testIgnoresEventsWithoutController(): void
    {
        $this->formGuard->expects($this->never())->method('guard');

        $this->observer->execute($this->eventFor(null));
    }

    public function testPassingRequestLeavesDataPersistorAlone(): void
    {
        $this->formGuard->expects($this->once())->method('guard')
            ->with('contact', $this->request, $this->response, 'https://shop.test/contact/index/')
            ->willReturn(true);
        $this->dataPersistor->expects($this->never())->method('set');

        $this->observer->execute($this->eventFor($this->action()));
    }

    public function testFailureStoresParamsWithoutToken(): void
    {
        $this->formGuard->method('guard')->willReturn(false);
        $this->request->method('getParams')->willReturn([
            'name' => 'Jeanne',
            'comment' => 'Bonjour',
            FormGuard::TOKEN_FIELD => 'XXXX.DUMMY.TOKEN.XXXX',
        ]);
        $this->dataPersistor->expects($this->once())->method('set')
            ->with('contact_us', ['name' => 'Jeanne', 'comment' => 'Bonjour']);

        $this->observer->execute($this->eventFor($this->action()));
    }
}
