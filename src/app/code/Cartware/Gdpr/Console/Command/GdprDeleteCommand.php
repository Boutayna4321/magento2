<?php
declare(strict_types=1);

namespace Cartware\Gdpr\Console\Command;

use Cartware\Gdpr\Api\GdprDeleteInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GdprDeleteCommand extends Command
{
    private const ARG_CUSTOMER_ID = 'customer_id';
    private const OPTION_CONFIRM = 'confirm';

    public function __construct(
        private readonly GdprDeleteInterface $gdprDeleteService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('cartware:gdpr:delete')
            ->setDescription('Anonymize the personal data of a customer (GDPR Art. 17).')
            ->addArgument(self::ARG_CUSTOMER_ID, InputArgument::REQUIRED, 'The customer ID')
            ->addOption(
                self::OPTION_CONFIRM,
                null,
                InputOption::VALUE_NONE,
                'Confirm the deletion'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption(self::OPTION_CONFIRM)) {
            $output->writeln('<comment>This action anonymizes the customer account. Run with --confirm to proceed.</comment>');
            return Cli::RETURN_FAILURE;
        }

        $customerId = (int) $input->getArgument(self::ARG_CUSTOMER_ID);

        try {
            $success = $this->gdprDeleteService->delete($customerId);
        } catch (NoSuchEntityException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        $output->writeln($success ? 'Customer data anonymized.' : 'Deletion failed.');

        return $success ? Cli::RETURN_SUCCESS : Cli::RETURN_FAILURE;
    }
}
