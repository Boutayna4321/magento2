<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Architecture;

use AlpineCommerce\Turnstile\Test\Unit\Architecture\Fixture\AllowedConstructor;
use AlpineCommerce\Turnstile\Test\Unit\Architecture\Fixture\ForbiddenConstructor;
use AlpineCommerce\Turnstile\Test\Unit\Architecture\Fixture\ForbiddenUnionConstructor;
use PHPUnit\Framework\TestCase;

/**
 * Rule R1: a production class of the module may only type-hint, in its constructor, classes that every
 * installation has (framework, Store, Config, the module itself, PSR). A constructor that type-hints a class
 * of a package removed by Composer makes `setup:di:compile` fail for the whole project.
 */
class ConstructorDependencyTest extends TestCase
{
    private ConstructorTypeChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ConstructorTypeChecker();
    }

    public function testModuleClassesOnlyDependOnAllowedTypes(): void
    {
        $violations = [];
        foreach (ConstructorTypeChecker::moduleClasses(dirname(__DIR__, 3)) as $className) {
            $violations = [...$violations, ...$this->checker->violations($className)];
        }

        $this->assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function testCheckerReportsForbiddenConstructorType(): void
    {
        $this->assertSame(
            [ForbiddenConstructor::class . '::__construct($router) uses Magento\Webapi\Controller\Rest\Router'],
            $this->checker->violations(ForbiddenConstructor::class)
        );
    }

    public function testCheckerReportsForbiddenTypeInsideUnion(): void
    {
        $this->assertSame(
            [ForbiddenUnionConstructor::class . '::__construct($session) uses Magento\Customer\Model\Session'],
            $this->checker->violations(ForbiddenUnionConstructor::class)
        );
    }

    public function testCheckerAcceptsBuiltinAndAllowedTypes(): void
    {
        $this->assertSame([], $this->checker->violations(AllowedConstructor::class));
    }

    public function testDiscoveryFindsProductionClassesAndSkipsTests(): void
    {
        $classes = ConstructorTypeChecker::moduleClasses(dirname(__DIR__, 3));

        $this->assertContains('AlpineCommerce\Turnstile\Model\Config', $classes);
        $this->assertContains('AlpineCommerce\Turnstile\Observer\ContactFormObserver', $classes);
        $this->assertSame([], array_values(array_filter(
            $classes,
            static fn (string $class): bool => str_contains($class, '\\Test\\')
        )));
    }
}
