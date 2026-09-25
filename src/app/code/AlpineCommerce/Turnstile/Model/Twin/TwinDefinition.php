<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Twin;

/**
 * An API endpoint that does the same thing as a protected form (decision D4 = B) and can be blocked
 * for anonymous callers: a service method (REST, asynchronous/bulk REST and SOAP) or a GraphQL mutation.
 * Every preset is a virtualType of this class in di.xml.
 */
class TwinDefinition
{
    private readonly ?string $serviceClass;
    private readonly ?string $serviceMethod;
    private readonly ?string $mutation;

    /**
     * @param string[] $requiredModules The twin is ignored unless all of them are enabled
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly string $id,
        private readonly string $label,
        private readonly array $requiredModules,
        ?string $serviceClass = null,
        ?string $serviceMethod = null,
        ?string $mutation = null,
        private readonly string $comment = '',
        private readonly int $sortOrder = 100
    ) {
        $serviceClass = $serviceClass === null ? '' : ltrim(trim($serviceClass), '\\');
        $serviceMethod = $serviceMethod === null ? '' : trim($serviceMethod);
        $mutation = $mutation === null ? '' : trim($mutation);
        $isService = $serviceClass !== '' && $serviceMethod !== '';
        $isMutation = $mutation !== '';
        if ($isService === $isMutation || ($serviceClass === '') !== ($serviceMethod === '')) {
            throw new \InvalidArgumentException(sprintf(
                'Turnstile API twin "%s" must target either a service method or a GraphQL mutation.',
                $id
            ));
        }
        $this->serviceClass = $isService ? $serviceClass : null;
        $this->serviceMethod = $isService ? $serviceMethod : null;
        $this->mutation = $isMutation ? $mutation : null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string[]
     */
    public function getRequiredModules(): array
    {
        return array_values($this->requiredModules);
    }

    public function getServiceClass(): ?string
    {
        return $this->serviceClass;
    }

    public function getServiceMethod(): ?string
    {
        return $this->serviceMethod;
    }

    public function getMutation(): ?string
    {
        return $this->mutation;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }
}
