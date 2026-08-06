<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BlogCategory extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('alphacommerce_blog_category', 'category_id');
    }
}
