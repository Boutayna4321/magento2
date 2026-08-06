<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Controller\Adminhtml\Question;

use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class DeleteList extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductQuestions::question';

    public function __construct(
        Context $context,
        private readonly QuestionRepositoryInterface $questionRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $ids = (array) $this->getRequest()->getParam('selected', []);

        foreach ($ids as $id) {
            try {
                $this->questionRepository->deleteById((int) $id);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Unable to delete question with ID "%1".', $id));
            }
        }

        if (!empty($ids)) {
            $this->messageManager->addSuccessMessage(__('Total questions deleted: %1', count($ids)));
        }

        return $this->_redirect('*/*/index');
    }
}
