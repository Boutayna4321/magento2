<?php
declare(strict_types=1);

namespace Cartware\Blog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BlogCategory extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('cartware_blog_category', 'category_id');
    }
}
