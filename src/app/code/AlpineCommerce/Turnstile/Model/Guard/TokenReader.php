<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Guard;

use Magento\Framework\App\Request\Http;

/**
 * Reads the Turnstile token from the POST body, then from the X-Turnstile-Token header (fetch/AJAX).
 * Never from the query string: a token in a URL ends up in logs, history and referers.
 */
class TokenReader
{
    public const FIELD = 'cf-turnstile-response';
    public const HEADER = 'X-Turnstile-Token';

    public function read(Http $request): string
    {
        $body = $request->getPostValue(self::FIELD);
        if (is_string($body) && trim($body) !== '') {
            return trim($body);
        }

        $header = $request->getHeader(self::HEADER);

        return is_string($header) ? trim($header) : '';
    }
}
