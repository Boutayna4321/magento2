<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Block\Adminhtml\Faq\Edit;

use AlpineCommerce\Faq\Api\FaqRepositoryInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    protected $context;

    protected $faqRepository;

    public function __construct(
        Context $context,
        FaqRepositoryInterface $faqRepository
    ) {
        $this->context = $context;
        $this->faqRepository = $faqRepository;
    }

    public function getFaqId()
    {
        try {
            return $this->faqRepository->getById(
                (int) $this->context->getRequest()->getParam('faq_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }

        return null;
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
