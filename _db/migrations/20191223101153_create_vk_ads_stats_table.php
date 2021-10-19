<?php

use Phinx\Migration\AbstractMigration;

class CreateVkAdsStatsTable extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('vk_ads_stats');
        $table->addColumn('vk_ads_campaign_id', 'integer')
            ->addForeignKey('vk_ads_campaign_id', 'vk_ads_campaign', 'id', ['delete' => 'CASCADE', 'update' => "CASCADE"])
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