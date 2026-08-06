<?php
declare(strict_types=1);

namespace Cartware\Faq\Block;

use Cartware\Faq\Api\Data\FaqInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class Detail extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getFaq(): FaqInterface
    {
        return $this->coreRegistry->registry('current_faq');
    }
}
