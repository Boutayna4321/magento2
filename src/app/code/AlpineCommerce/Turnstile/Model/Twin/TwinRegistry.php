<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Twin;

use Magento\Framework\Module\Manager as ModuleManager;

/**
 * API twins collected from di.xml. A twin whose modules are not all enabled is hidden and never applied.
 * Service classes and methods compare case-insensitively (as PHP does); GraphQL field names are
 * case-sensitive and compare exactly. Configuration errors throw on first use.
 */
class TwinRegistry
{
    public const ID_PATTERN = '/^[a-z0-9_]{1,64}$/';

    /**
     * @var array{services: array<string, TwinDefinition>, mutations: array<string, TwinDefinition>}|null
     */
    private ?array $index = null;

    /**
     * @param array<string, mixed> $twins Twin definitions keyed by their id
     */
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly array $twins = []
    ) {
    }

    /**
     * @return array<string, TwinDefinition> Available twins keyed by id, by sort order then id
     */
    public function getAll(): array
    {
        $this->index();
        $available = array_filter($this->twins, fn (TwinDefinition $twin): bool => $this->isAvailable($twin));
        uasort(
            $available,
            static fn (TwinDefinition $a, TwinDefinition $b): int =>
                [$a->getSortOrder(), $a->getId()] <=> [$b->getSortOrder(), $b->getId()]
        );

        return $available;
    }

    public function findByService(string $serviceClass, string $serviceMethod): ?TwinDefinition
    {
        $twin = $this->index()['services'][$this->serviceKey($serviceClass, $serviceMethod)] ?? null;

        return $twin !== null && $this->isAvailable($twin) ? $twin : null;
    }

    public function findByMutation(string $mutation): ?TwinDefinition
    {
        $twin = $this->index()['mutations'][$mutation] ?? null;

        return $twin !== null && $this->isAvailable($twin) ? $twin : null;
    }

    private function isAvailable(TwinDefinition $twin): bool
    {
        foreach ($twin->getRequiredModules() as $module) {
            if (!$this->moduleManager->isEnabled($module)) {
                return false;
            }
        }

        return true;
    }

    private function serviceKey(string $serviceClass, string $serviceMethod): string
    {
        return strtolower(ltrim($serviceClass, '\\')) . '::' . strtolower($serviceMethod);
    }

    /**
     * @return array{services: array<string, TwinDefinition>, mutations: array<string, TwinDefinition>}
     */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $index = ['services' => [], 'mutations' => []];
        foreach ($this->twins as $key => $twin) {
            if (!$twin instanceof TwinDefinition) {
                throw new \InvalidArgumentException(
                    sprintf('Turnstile API twin "%s" must be a %s.', $key, TwinDefinition::class)
                );
            }
            $id = $twin->getId();
            if (preg_match(self::ID_PATTERN, $id) !== 1) {
                throw new \InvalidArgumentException(sprintf('Invalid Turnstile API twin id "%s".', $id));
            }
            if ($key !== $id) {
                throw new \InvalidArgumentException(
                    sprintf('Turnstile API twin "%s" is registered under the key "%s".', $id, $key)
                );
            }
            [$bucket, $target] = $twin->getMutation() !== null
                ? ['mutations', $twin->getMutation()]
                : ['services', $this->serviceKey((string) $twin->getServiceClass(), (string) $twin->getServiceMethod())];
            if (isset($index[$bucket][$target])) {
                throw new \InvalidArgumentException(sprintf(
                    '"%s" is claimed by API twins "%s" and "%s".',
                    $target,
                    $index[$bucket][$target]->getId(),
                    $id
                ));
            }
            $index[$bucket][$target] = $twin;
        }

        return $this->index = $index;
    }
}
