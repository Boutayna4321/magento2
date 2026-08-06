<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model\ResourceModel\BlogPost;

use AlpineCommerce\Blog\Model\BlogPost as BlogPostModel;
use AlpineCommerce\Blog\Model\ResourceModel\BlogPost as BlogPostResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(BlogPostModel::class, BlogPostResource::class);
    }
}
