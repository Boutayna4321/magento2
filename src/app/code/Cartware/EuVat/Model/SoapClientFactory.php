<?php
declare(strict_types=1);

namespace Cartware\EuVat\Model;

/**
 * Creates SOAP clients with a safe default timeout.
 */
class SoapClientFactory
{
    /**
     * @param string $wsdl
     * @param int $timeout
     * @return \SoapClient
     */
    public function create(string $wsdl, int $timeout = 10): \SoapClient
    {
        return new \SoapClient($wsdl, [
            'connection_timeout' => $timeout,
            'exceptions' => true,
            'trace' => true,
        ]);
    }
}
