<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Forms that Turnstile can protect, collected from di.xml. A form whose required module is disabled
 * is hidden and never applied. Configuration errors are developer errors and throw on first use.
 */
class FormRegistry
{
    public const ID_PATTERN = '/^[a-z0-9_]{1,32}$/';

    /**
     * @var array<string, FormDefinitionInterface>|null
     */
    private ?array $byAction = null;

    /**
     * @param array<string, mixed> $forms Form definitions keyed by their id
     */
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly array $forms = []
    ) {
    }

    /**
     * @return array<string, FormDefinitionInterface> Available forms keyed by id, by sort order then id
     */
    public function getAll(): array
    {
        $this->index();
        $available = array_filter($this->forms, fn (FormDefinitionInterface $form): bool => $this->isAvailable($form));
        uasort(
            $available,
            static fn (FormDefinitionInterface $a, FormDefinitionInterface $b): int =>
                [$a->getSortOrder(), $a->getId()] <=> [$b->getSortOrder(), $b->getId()]
        );

        return $available;
    }

    public function get(string $id): ?FormDefinitionInterface
    {
        return $this->getAll()[$id] ?? null;
    }

    /**
     * @param string $fullActionName Full action name from the Magento router (route_controller_action)
     */
    public function findByAction(string $fullActionName): ?FormDefinitionInterface
    {
        $form = $this->index()[strtolower($fullActionName)] ?? null;

        return $form !== null && $this->isAvailable($form) ? $form : null;
    }

    private function isAvailable(FormDefinitionInterface $form): bool
    {
        $module = $form->getRequiredModule();

        return $module === null || $module === '' || $this->moduleManager->isEnabled($module);
    }

    /**
     * @return array<string, FormDefinitionInterface>
     */
    private function index(): array
    {
        if ($this->byAction !== null) {
            return $this->byAction;
        }

        $byAction = [];
        foreach ($this->forms as $key => $form) {
            if (!$form instanceof FormDefinitionInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Turnstile form "%s" must implement %s.', $key, FormDefinitionInterface::class)
                );
            }
            $id = $form->getId();
            if (preg_match(self::ID_PATTERN, $id) !== 1) {
                throw new \InvalidArgumentException(sprintf('Invalid Turnstile form id "%s".', $id));
            }
            if ($key !== $id) {
                throw new \InvalidArgumentException(
                    sprintf('Turnstile form "%s" is registered under the key "%s".', $id, $key)
                );
            }
            foreach ($form->getActions() as $action) {
                if (isset($byAction[$action])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Action "%s" is claimed by Turnstile forms "%s" and "%s".',
                        $action,
                        $byAction[$action]->getId(),
                        $id
                    ));
                }
                $byAction[$action] = $form;
            }
        }

        return $this->byAction = $byAction;
    }
}
