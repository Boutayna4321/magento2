<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Image\Adapter\AdapterInterface;
use Magento\Framework\Image\Factory as ImageFactory;
use Magento\Framework\UrlInterface;

class Image extends AbstractHelper
{
    public const UPLOAD_DIR = 'alp_reviews';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly UrlInterface $urlBuilder
    ) {
        parent::__construct();
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
