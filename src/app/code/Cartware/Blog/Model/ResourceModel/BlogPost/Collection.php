<?php
declare(strict_types=1);

namespace Cartware\Blog\Model\ResourceModel\BlogPost;

use Cartware\Blog\Model\BlogPost as BlogPostModel;
use Cartware\Blog\Model\ResourceModel\BlogPost as BlogPostResource;
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
