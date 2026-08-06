<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Controller\Adminhtml\Faq;

use AlpineCommerce\Faq\Api\FaqRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_Faq::faq';

    public function __construct(
        Context $context,
        private readonly FaqRepositoryInterface $faqRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('faq_id');

        try {
            $this->faqRepository->delete($this->faqRepository->getById($id));
            $this->messageManager->addSuccessMessage(__('The FAQ entry has been deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This FAQ entry no longer exists.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete the FAQ entry.'));
        }

        return $this->_redirect('*/*/index');
    }
}
