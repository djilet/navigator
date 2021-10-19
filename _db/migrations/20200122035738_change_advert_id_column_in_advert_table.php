<?php

use Phinx\Migration\AbstractMigration;

class ChangeAdvertIdColumnInAdvertTable extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $users = $this->table('vk_ads_advert');
        $users->changeColumn('advert_id', 'string', ['limit' => 255])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {

    }
}
