<?php
namespace Cartware\Training\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Cartware\Training\Helper\Data;

class StoreInfo extends Template
{
    protected $helper;

    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    public function getStoreName()
    {
        return $this->helper->getStoreName();
    }

    public function getStoreId()
    {
        return $this->helper->getStoreId();
    }

    public function getStoreUrl()
    {
        return $this->helper->getStoreUrl();
    }

    public function isDisplayStoreInfo()
    {
        return $this->helper->isDisplayStoreInfo();
    }
}
