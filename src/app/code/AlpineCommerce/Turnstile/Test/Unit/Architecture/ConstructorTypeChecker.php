<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Architecture;

/**
 * Test utility for rule R1: lists the module's production classes and reports every constructor parameter,
 * parent class or interface whose type lies outside the allowed namespaces.
 * Type names are read with reflection only: a missing class is reported, never loaded.
 */
class ConstructorTypeChecker
{
    public const ALLOWED_PREFIXES = [
        'Magento\\Framework\\',
        'Magento\\Store\\',
        'Magento\\Config\\',
        'AlpineCommerce\\Turnstile\\',
        'Psr\\',
    ];

    private const MODULE_NAMESPACE = 'AlpineCommerce\\Turnstile\\';

    /**
     * @return string[] Production class names of the module (files outside Test/, except registration.php)
     */
    public static function moduleClasses(string $moduleDir): array
    {
        $moduleDir = rtrim($moduleDir, '/');
        $classes = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($moduleDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen($moduleDir) + 1);
            if ($file->getExtension() !== 'php'
                || $relative === 'registration.php'
                || str_starts_with($relative, 'Test/')
            ) {
                continue;
            }
            $classes[] = self::MODULE_NAMESPACE . str_replace('/', '\\', substr($relative, 0, -4));
        }
        sort($classes);

        return $classes;
    }

    /**
     * @return string[] One message per forbidden type
     */
    public function violations(string $className): array
    {
        $class = new \ReflectionClass($className);
        $violations = [];

        $parent = $class->getParentClass();
        if ($parent !== false && !$this->isAllowed($parent->getName())) {
            $violations[] = sprintf('%s extends %s', $className, $parent->getName());
        }
        foreach ($class->getInterfaceNames() as $interface) {
            if (!$this->isAllowed($interface)) {
                $violations[] = sprintf('%s implements %s', $className, $interface);
            }
        }

        $constructor = $class->getConstructor();
        if ($constructor === null || $constructor->getDeclaringClass()->getName() !== $class->getName()) {
            // An inherited constructor belongs to the parent class, already checked above.
            return $violations;
        }
        foreach ($constructor->getParameters() as $parameter) {
            foreach ($this->typeNames($parameter->getType()) as $type) {
                if (!$this->isAllowed($type)) {
                    $violations[] = sprintf('%s::__construct($%s) uses %s', $className, $parameter->getName(), $type);
                }
            }
        }

        return $violations;
    }

    /**
     * @return string[] Class and interface names found in the type (builtin types, self and static skipped)
     */
    private function typeNames(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            return $type->isBuiltin() || in_array($type->getName(), ['self', 'static'], true)
                ? []
                : [ltrim($type->getName(), '\\')];
        }
        if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
            $names = [];
            foreach ($type->getTypes() as $inner) {
                $names = [...$names, ...$this->typeNames($inner)];
            }
            return $names;
        }

        return [];
    }

    private function isAllowed(string $type): bool
    {
        if ($this->isPhpInternal($type)) {
            return true;
        }
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($type, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * PHP's own classes (Throwable, RuntimeException, ArrayAccess...) exist on every installation.
     * Checked without autoloading, so a missing class is never loaded.
     */
    private function isPhpInternal(string $type): bool
    {
        if (!class_exists($type, false) && !interface_exists($type, false)) {
            return false;
        }

        return (new \ReflectionClass($type))->isInternal();
    }
}
