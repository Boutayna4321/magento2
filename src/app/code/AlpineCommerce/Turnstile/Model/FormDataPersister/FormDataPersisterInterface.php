<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\FormDataPersister;

use Magento\Framework\App\Request\Http;

/**
 * Keeps what the customer typed after a Turnstile rejection, so the form can be pre-filled.
 * Implementations never keep passwords, the Turnstile token or the form key.
 */
interface FormDataPersisterInterface
{
    public function persist(Http $request): void;
}
