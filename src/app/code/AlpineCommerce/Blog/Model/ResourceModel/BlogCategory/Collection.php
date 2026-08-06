<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model\ResourceModel\BlogCategory;

use AlpineCommerce\Blog\Model\BlogCategory as BlogCategoryModel;
use AlpineCommerce\Blog\Model\ResourceModel\BlogCategory as BlogCategoryResource;
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
