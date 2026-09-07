<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Controller\Adminhtml\Validation;

use AlpineCommerce\EuVat\Api\VatValidationInterface as VatValidationServiceInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\PageFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;

class Validate extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_EuVat::validation_validate';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly VatValidationServiceInterface $vatValidationService,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ManagerInterface $messageManager
    ) {
        parent::__construct($context);
    }

    public function execute(): Page|ResultInterface
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('AlpineCommerce_EuVat::validation');
        $resultPage->getConfig()->getTitle()->prepend(__('Validate VAT Number'));

        $postData = $this->getRequest()->getPostValue();
        if ($postData && isset($postData['country_id'], $postData['vat_number'])) {
            $countryId = trim((string) $postData['country_id']);
            $vatNumber = trim((string) $postData['vat_number']);

            if ($countryId === '' || $vatNumber === '') {
                $this->messageManager->addErrorMessage(__('Country code and VAT number are required.'));
                return $resultPage;
            }

            try {
                $validation = $this->vatValidationService->validate($countryId, $vatNumber);
                $this->messageManager->addSuccessMessage(
                    $validation->isValid()
                        ? __('VAT number %1 is VALID. Company: %2', $vatNumber, $validation->getName() ?? 'N/A')
                        : __('VAT number %1 is INVALID.', $vatNumber)
                );
                $this->dataPersistor->set('euvat_validation', [
                    'country_id' => $countryId,
                    'vat_number' => $vatNumber,
                    'is_valid' => $validation->isValid(),
                    'name' => $validation->getName(),
                    'address' => $validation->getAddress(),
                    'request_date' => $validation->getRequestDate()
                ]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Validation failed: %1', $e->getMessage()));
            }
        }

        return $resultPage;
    }
}
