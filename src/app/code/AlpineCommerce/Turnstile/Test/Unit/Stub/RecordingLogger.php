<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Stub;

use Psr\Log\AbstractLogger;

/**
 * Test double: keeps every log record so tests can check levels and that no sensitive value is logged.
 */
class RecordingLogger extends AbstractLogger
{
    /**
     * @var array<int, array{level: mixed, message: string, context: array<mixed>}>
     */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }
}
