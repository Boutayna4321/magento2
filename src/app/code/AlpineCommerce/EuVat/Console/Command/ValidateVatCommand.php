<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Console\Command;

use AlpineCommerce\EuVat\Api\VatValidationInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateVatCommand extends Command
{
    private const ARG_COUNTRY_CODE = 'country_code';
    private const ARG_VAT_NUMBER = 'vat_number';

    public function __construct(
        private readonly VatValidationInterface $vatValidationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('alphacommerce:euvat:validate')
            ->setDescription('Validate a European VAT number against the VIES web service.')
            ->addArgument(
                self::ARG_COUNTRY_CODE,
                InputArgument::REQUIRED,
                'The two-letter ISO country code (e.g. FR)'
            )
            ->addArgument(
                self::ARG_VAT_NUMBER,
                InputArgument::REQUIRED,
                'The VAT number without the country prefix'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $countryCode = (string) $input->getArgument(self::ARG_COUNTRY_CODE);
        $vatNumber = (string) $input->getArgument(self::ARG_VAT_NUMBER);

        try {
            $result = $this->vatValidationService->validate($countryCode, $vatNumber);
        } catch (LocalizedException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        $output->writeln('Valid: ' . ($result->isValid() ? 'yes' : 'no'));
        $output->writeln('Country: ' . $result->getCountryId());
        $output->writeln('VAT Number: ' . $result->getVatNumber());
        $output->writeln('Name: ' . ($result->getName() ?? '-'));
        $output->writeln('Address: ' . ($result->getAddress() ?? '-'));

        return Cli::RETURN_SUCCESS;
    }
}
