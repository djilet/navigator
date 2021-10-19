<?php

use Phinx\Migration\AbstractMigration;

class CreateVkAdsAdvertTable extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('vk_ads_advert');
        $table->addColumn('vk_ads_campaign_id', 'integer')
            ->addForeignKey('vk_ads_campaign_id', 'vk_ads_campaign', 'id', ['delete' => 'CASCADE', 'update' => "CASCADE"])
            ->addColumn('advert_id', 'integer')
            ->addColumn('name', 'string', ['limit' => 100])
            ->save();
    }

    public function down()
    {

    }
}
