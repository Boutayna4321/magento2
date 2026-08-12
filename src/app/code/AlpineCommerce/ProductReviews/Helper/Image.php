<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;

class Image extends AbstractHelper
{
    public const UPLOAD_DIR = 'alp_reviews';

    public function __construct(
        Context $context,
        private readonly Filesystem $filesystem,
        private readonly UrlInterface $urlBuilder
    ) {
        parent::__construct($context);
    }

    public function getUploadDir(): string
    {
        return $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)
            ->getAbsolutePath(self::UPLOAD_DIR);
    }

    public function getUploadUrl(): string
    {
        return $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
            . self::UPLOAD_DIR;
    }
}
