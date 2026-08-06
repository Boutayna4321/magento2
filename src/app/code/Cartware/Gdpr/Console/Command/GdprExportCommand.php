<?php
declare(strict_types=1);

namespace Cartware\Gdpr\Console\Command;

use Cartware\Gdpr\Api\GdprExportInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GdprExportCommand extends Command
{
    private const ARG_CUSTOMER_ID = 'customer_id';

    public function __construct(
        private readonly GdprExportInterface $gdprExportService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('cartware:gdpr:export')
            ->setDescription('Export all personal data of a customer (GDPR Art. 15).')
            ->addArgument(self::ARG_CUSTOMER_ID, InputArgument::REQUIRED, 'The customer ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $customerId = (int) $input->getArgument(self::ARG_CUSTOMER_ID);

        try {
            $data = $this->gdprExportService->export($customerId);
        } catch (NoSuchEntityException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        $output->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Cli::RETURN_SUCCESS;
    }
}
