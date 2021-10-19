<?php

use Phinx\Migration\AbstractMigration;

class CreateBannerItemPageTable extends AbstractMigration
{
    public function change()
    {
        $this->table('banner_item_page', ['id' => 'PageID'])
            ->addColumn('ItemID', 'integer')
            ->addColumn('StaticPath', 'string')
            ->addForeignKey('ItemID', 'banner_item', 'ItemID', ['delete' => 'CASCADE', 'update' => "CASCADE"])
            ->create();
    }
}
