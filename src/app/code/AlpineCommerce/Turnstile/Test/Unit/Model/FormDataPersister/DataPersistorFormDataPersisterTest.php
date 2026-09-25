<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\FormDataPersister;

use AlpineCommerce\Turnstile\Model\FormDataPersister\DataPersistorFormDataPersister;
use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataFilter;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class DataPersistorFormDataPersisterTest extends TestCase
{
    public function testKeepsTheFilteredPostBodyUnderItsKey(): void
    {
        $request = $this->createMock(Http::class);
        $request->method('getPostValue')->willReturn([
            'name' => 'Jane',
            'comment' => 'Hello',
            'form_key' => 'abcd1234',
            'cf-turnstile-response' => 'token',
            'hideit' => '',
        ]);
        $dataPersistor = $this->createMock(DataPersistorInterface::class);
        $dataPersistor->expects($this->once())->method('set')
            ->with('contact_us', ['name' => 'Jane', 'comment' => 'Hello']);

        (new DataPersistorFormDataPersister($dataPersistor, new FormDataFilter(), 'contact_us', ['hideit']))->persist($request);
    }
}
