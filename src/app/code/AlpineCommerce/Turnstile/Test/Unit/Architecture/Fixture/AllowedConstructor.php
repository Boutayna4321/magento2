<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Architecture\Fixture;

/**
 * Test fixture: only builtin types, PHP's own classes (Throwable) and allowed namespaces.
 */
class AllowedConstructor
{
    /**
     * @var array<int, mixed>
     */
    public array $arguments;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        string $name,
        ?int $count,
        object $untyped,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \AlpineCommerce\Turnstile\Model\Config $config,
        ?\Throwable $previous = null,
        array $items = []
    ) {
        $this->arguments = [$name, $count, $untyped, $logger, $request, $storeManager, $config, $previous, $items];
    }
}
