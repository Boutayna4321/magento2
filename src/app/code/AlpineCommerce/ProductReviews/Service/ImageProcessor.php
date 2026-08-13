<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;

class ImageProcessor
{
    public const UPLOAD_DIR = 'alp_reviews';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly UrlInterface $urlBuilder
    ) {
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
