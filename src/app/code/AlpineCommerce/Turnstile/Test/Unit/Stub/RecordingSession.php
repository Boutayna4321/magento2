<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Stub;

use Magento\Framework\Session\Generic;

/**
 * Test double: a session that records its magic setter calls instead of writing $_SESSION.
 */
class RecordingSession extends Generic
{
    /**
     * @var array<int, array{0: string, 1: array<mixed>}>
     */
    public array $calls = [];

    /**
     * The parent constructor starts a real session; the double does not need it.
     */
    public function __construct()
    {
    }

    /**
     * @param string $method
     * @param array<mixed> $args
     */
    public function __call($method, $args)
    {
        $this->calls[] = [$method, $args];
        return $this;
    }
}
