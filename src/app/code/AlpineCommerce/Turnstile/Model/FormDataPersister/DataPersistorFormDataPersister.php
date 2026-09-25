<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\FormDataPersister;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\Http;

/**
 * Keeps the filtered input in Magento's DataPersistor under a key (the contact form reads "contact_us").
 */
class DataPersistorFormDataPersister implements FormDataPersisterInterface
{
    /**
     * @param string[] $excludedFields
     */
    public function __construct(
        private readonly DataPersistorInterface $dataPersistor,
        private readonly FormDataFilter $filter,
        private readonly string $key,
        private readonly array $excludedFields = []
    ) {
    }

    public function persist(Http $request): void
    {
        $this->dataPersistor->set($this->key, $this->filter->filter($request, $this->excludedFields));
    }
}
