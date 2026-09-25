<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\FormDataPersister;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManager\ConfigInterface as ObjectManagerConfig;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Keeps the filtered input in the session a Magento form reads back (decision D7), without any
 * compile-time dependency on the module that owns that session.
 *
 * The session type and setter are strings from di.xml (for example Magento\Customer\Model\Session and
 * setCustomerFormData). The session is fetched only when a rejected request is persisted, only if the
 * owning module is enabled, and by its own name so that the shared instance the native controller uses
 * is the one written (virtual types included). This class acts as a factory, the only place of the
 * module that uses the object manager directly.
 *
 * A misconfiguration that code can detect (unknown type, type that is not a session, malformed setter)
 * throws in developer mode and is logged without any form data otherwise. A well-formed but misspelled
 * setter cannot be detected here (sessions accept any set* call); integration tests cover each preset.
 */
class SessionFormDataPersister implements FormDataPersisterInterface
{
    private const SETTER_PATTERN = '/^set[A-Z][A-Za-z0-9]*$/';

    /**
     * @param string[] $excludedFields
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ObjectManagerInterface $objectManager,
        private readonly ObjectManagerConfig $objectManagerConfig,
        private readonly FormDataFilter $filter,
        private readonly State $appState,
        private readonly LoggerInterface $logger,
        private readonly string $requiredModule,
        private readonly string $sessionType,
        private readonly string $setter,
        private readonly ?string $valueField = null,
        private readonly array $excludedFields = []
    ) {
    }

    public function persist(Http $request): void
    {
        if (!$this->moduleManager->isEnabled($this->requiredModule)) {
            return;
        }

        $problem = $this->configurationProblem();
        if ($problem !== null) {
            $this->report($problem);
            return;
        }

        $data = $this->filter->filter($request, $this->excludedFields);
        $value = $this->valueField === null ? $data : $this->valueAt($data, $this->valueField);
        if ($this->valueField !== null && !is_string($value)) {
            return;
        }

        $session = $this->objectManager->get($this->sessionType);
        if (!$session instanceof SessionManagerInterface) {
            $this->report('type is not a session');
            return;
        }
        $session->{$this->setter}($value);
    }

    private function configurationProblem(): ?string
    {
        if (preg_match(self::SETTER_PATTERN, $this->setter) !== 1) {
            return 'malformed setter';
        }
        $class = $this->objectManagerConfig->getInstanceType($this->sessionType);
        if (!class_exists($class)) {
            return 'unknown session type';
        }
        if (!is_subclass_of($class, SessionManagerInterface::class)) {
            return 'type is not a session';
        }

        return null;
    }

    /**
     * @param array<string|int, mixed> $data
     */
    private function valueAt(array $data, string $path): mixed
    {
        foreach (explode('/', $path) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return null;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    private function report(string $problem): void
    {
        $message = sprintf(
            'Turnstile form data persister misconfigured (%s): %s::%s',
            $problem,
            $this->sessionType,
            $this->setter
        );
        if ($this->appState->getMode() === State::MODE_DEVELOPER) {
            throw new \LogicException($message);
        }
        $this->logger->error($message);
    }
}
