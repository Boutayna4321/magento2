<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Test\Unit\Model\Queue;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceMessageInterface;
use AlpineCommerce\AutoInvoice\Model\Data\InvoiceMessageFactory;
use AlpineCommerce\AutoInvoice\Model\Queue\InvoicePublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class InvoicePublisherTest extends TestCase
{
    private InvoicePublisher $publisher;
    private PublisherInterface&MockObject $messagePublisher;
    private InvoiceMessageInterface&MockObject $message;

    protected function setUp(): void
    {
        $this->messagePublisher = $this->createMock(PublisherInterface::class);
        $this->message = $this->createMock(InvoiceMessageInterface::class);
        $messageFactory = $this->getMockBuilder(InvoiceMessageFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $messageFactory->method('create')->willReturn($this->message);

        $this->publisher = new InvoicePublisher(
            $this->messagePublisher,
            $messageFactory,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * The topic is the one declared in etc/communication.xml, queue_publisher.xml and queue_topology.xml.
     */
    public function testPublishSendsTheMessageToTheDeclaredTopic(): void
    {
        $this->message->expects($this->once())->method('setOrderId')->with(42);
        $this->message->expects($this->once())->method('setStoreId')->with(1);
        $this->message->expects($this->once())->method('setCorrelationId')->with('test-correlation');
        $this->message->expects($this->once())->method('setCreatedAt');

        $this->messagePublisher->expects($this->once())
            ->method('publish')
            ->with('alpinecommerce.autoinvoice.process', $this->identicalTo($this->message));

        $this->publisher->publish(42, 1, 'test-correlation');
    }
}
