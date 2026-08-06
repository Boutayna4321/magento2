<?php
declare(strict_types=1);

namespace Cartware\Blog\Model\ResourceModel\BlogCategory;

use Cartware\Blog\Model\BlogCategory as BlogCategoryModel;
use Cartware\Blog\Model\ResourceModel\BlogCategory as BlogCategoryResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(BlogCategoryModel::class, BlogCategoryResource::class);
    }
}
