<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

class Index extends Action
{
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set(__('Product Questions'));

        return $page;
    }
}
