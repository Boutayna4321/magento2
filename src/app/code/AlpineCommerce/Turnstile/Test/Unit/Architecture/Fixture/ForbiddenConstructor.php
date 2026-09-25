<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Architecture\Fixture;

/**
 * Test fixture: type-hints a class of an optional module (Magento_Webapi) in its constructor.
 * The class is never instantiated; reflection reads the type name without loading it.
 */
class ForbiddenConstructor
{
    /**
     * @var array<int, mixed>
     */
    public array $arguments;

    /**
     */
    public function __construct(string $name, \Magento\Webapi\Controller\Rest\Router $router)
    {
        $this->arguments = [$name, $router];
    }
}
