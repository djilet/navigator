<?php

use Phinx\Migration\AbstractMigration;

class CreateVkAdsAdvertStatsTable extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('vk_ads_advert_stats');
        $table->addColumn('vk_ads_advert_id', 'integer')
            ->addForeignKey('vk_ads_advert_id', 'vk_ads_advert', 'id', ['delete' => 'CASCADE', 'update' => "CASCADE"])
            ->addColumn('day', 'string', ['limit' => 100])
            ->addColumn('spent', 'float', ['null' => true])
            ->addColumn('impressions', 'integer', ['null' => true])
            ->addColumn('clicks', 'integer', ['null' => true])
            ->addColumn('reach', 'integer', ['null' => true])
            ->save();
    }

    public function down()
    {

    }
}
