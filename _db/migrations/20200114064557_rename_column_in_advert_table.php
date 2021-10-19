<?php

use Phinx\Migration\AbstractMigration;

class RenameColumnInAdvertTable extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('vk_ads_advert');
        $table->renameColumn('name', 'city')->update();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('vk_ads_advert');
        $table->renameColumn('city', 'name')->update();

    }
}
