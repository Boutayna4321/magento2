<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Block;

use Cartware\LegalPages\Api\Data\LegalPageInterface;
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

    public function getPage(): LegalPageInterface
    {
        return $this->coreRegistry->registry('current_legal_page');
    }
}
