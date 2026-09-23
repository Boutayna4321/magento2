<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Test\Unit\Model\Queue;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceMessageInterface;
use AlpineCommerce\AutoInvoice\Model\Queue\InvoicePublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvoicePublisherTest extends TestCase
{
    private InvoicePublisher $publisher;
    private PublisherInterface&MockObject $messagePublisher;

    protected function setUp(): void
    {
        $this->messagePublisher = $this->createMock(PublisherInterface::class);
        $this->publisher = new InvoicePublisher($this->messagePublisher);
    }

    public function testPublishSendsToCorrectTopic(): void
    {
        $orderId = 42;
        $storeId = 1;
        $correlationId = 'test-correlation';

        $this->messagePublisher->expects($this->once())
            ->method('publish')
            ->with(
                'alpincommerce.autoinvoice.process',
                $this->callback(function ($message) use ($orderId, $storeId, $correlationId) {
                    return $message instanceof InvoiceMessageInterface
                        && $message->getOrderId() === $orderId
                        && $message->getStoreId() === $storeId
                        && $message->getCorrelationId() === $correlationId;
                })
            );

        $this->publisher->publish($orderId, $storeId, $correlationId);
    }

    public function testPublishGeneratesCorrelationIdWhenNull(): void
    {
        $this->messagePublisher->expects($this->once())
            ->method('publish')
            ->with(
                'alpincommerce.autoinvoice.process',
                $this->callback(function ($message) {
                    return $message instanceof InvoiceMessageInterface
                        && $message->getCorrelationId() !== null
                        && $message->getCorrelationId() !== '';
                })
            );

        $this->publisher->publish(100, 0);
    }
}
