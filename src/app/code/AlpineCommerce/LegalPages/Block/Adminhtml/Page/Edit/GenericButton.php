<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Block\Adminhtml\Page\Edit;

use AlpineCommerce\LegalPages\Api\LegalPageRepositoryInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    protected $context;

    protected $pageRepository;

    public function __construct(
        Context $context,
        LegalPageRepositoryInterface $pageRepository
    ) {
        $this->context = $context;
        $this->pageRepository = $pageRepository;
    }

    public function getPageId()
    {
        try {
            return $this->pageRepository->getById(
                (int) $this->context->getRequest()->getParam('page_id')
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
