<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataPersisterInterface;

/**
 * Generic form definition: every preset is a virtualType of this class in di.xml.
 */
class FormDefinition implements FormDefinitionInterface
{
    private const FAILURE_RESPONSES = [
        self::FAILURE_RESPONSE_AUTO,
        self::FAILURE_RESPONSE_REDIRECT,
        self::FAILURE_RESPONSE_JSON,
    ];

    /**
     * @var string[]
     */
    private readonly array $actions;

    /**
     * @param string[] $actions
     * @param array<string, mixed> $redirectParams
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly string $id,
        private readonly string $label,
        array $actions,
        private readonly ?string $requiredModule = null,
        private readonly string $failureResponse = self::FAILURE_RESPONSE_AUTO,
        private readonly ?string $redirectPath = null,
        private readonly array $redirectParams = [],
        private readonly ?FormDataPersisterInterface $persister = null,
        private readonly ?string $recaptchaConfigPath = null,
        private readonly int $sortOrder = 100
    ) {
        if (!in_array($failureResponse, self::FAILURE_RESPONSES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown Turnstile failure response "%s".', $failureResponse));
        }
        $normalised = array_values(array_unique(array_filter(
            array_map(static fn ($action): string => strtolower(trim((string) $action)), $actions),
            static fn (string $action): bool => $action !== ''
        )));
        if ($normalised === []) {
            throw new \InvalidArgumentException(sprintf('Turnstile form "%s" declares no action.', $id));
        }
        $this->actions = $normalised;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getRequiredModule(): ?string
    {
        return $this->requiredModule;
    }

    public function getFailureResponse(): string
    {
        return $this->failureResponse;
    }

    public function getRedirectPath(): ?string
    {
        return $this->redirectPath;
    }

    public function getRedirectParams(): array
    {
        return $this->redirectParams;
    }

    public function getPersister(): ?FormDataPersisterInterface
    {
        return $this->persister;
    }

    public function getRecaptchaConfigPath(): ?string
    {
        return $this->recaptchaConfigPath;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
