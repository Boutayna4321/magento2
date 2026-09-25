<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\FormDataPersister;

use Magento\Framework\App\Request\Http;

/**
 * Customer input that may be kept after a rejection: the POST body only (never the query string),
 * without the Turnstile token, the form key or any password field, at any depth and in any case,
 * and without the paths a form excludes (notation "parent/child").
 */
class FormDataFilter
{
    public const ALWAYS_EXCLUDED = [
        'cf-turnstile-response',
        'form_key',
        'password',
        'password_confirmation',
        'current_password',
    ];

    /**
     * @param string[] $excludedPaths
     * @return array<string|int, mixed>
     */
    public function filter(Http $request, array $excludedPaths = []): array
    {
        $post = $request->getPostValue();
        if (!is_array($post)) {
            return [];
        }

        $data = $this->withoutSensitiveKeys($post);
        foreach ($excludedPaths as $path) {
            $data = $this->withoutPath($data, array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== '')));
        }

        return $data;
    }

    /**
     * @param array<string|int, mixed> $data
     * @return array<string|int, mixed>
     */
    private function withoutSensitiveKeys(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::ALWAYS_EXCLUDED, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->withoutSensitiveKeys($value);
            }
        }

        return $data;
    }

    /**
     * @param array<string|int, mixed> $data
     * @param string[] $segments
     * @return array<string|int, mixed>
     */
    private function withoutPath(array $data, array $segments): array
    {
        $key = array_shift($segments);
        if ($key === null || !array_key_exists($key, $data)) {
            return $data;
        }
        if ($segments === []) {
            unset($data[$key]);
        } elseif (is_array($data[$key])) {
            $data[$key] = $this->withoutPath($data[$key], $segments);
        }

        return $data;
    }
}
