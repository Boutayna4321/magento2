<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Architecture\Fixture;

/**
 * Test fixture: an optional-module class hidden inside a nullable union type.
 */
class ForbiddenUnionConstructor
{
    /**
     * @var array<int, mixed>
     */
    public array $arguments;

    /**
     */
    public function __construct(\Magento\Customer\Model\Session|\Psr\Log\LoggerInterface|null $session = null)
    {
        $this->arguments = [$session];
    }
}
