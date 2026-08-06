<?php
declare(strict_types=1);

namespace Cartware\Blog\Block;

use Cartware\Blog\Api\Data\BlogPostInterface;
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

    public function getPost(): BlogPostInterface
    {
        return $this->coreRegistry->registry('current_post');
    }
}
