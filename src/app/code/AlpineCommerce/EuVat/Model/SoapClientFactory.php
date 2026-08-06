<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Model;

class SoapClientFactory
{
    public function create(string $wsdl, int $timeout = 10): \SoapClient
    {
        return new \SoapClient($wsdl, [
            'connection_timeout' => $timeout,
            'exceptions' => true,
            'trace' => true,
        ]);
    }
}
