<?php

use Phinx\Migration\AbstractMigration;

class RenameVkAdsTable extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('vk_ads_campaign');
        $table
            ->rename('ads_campaign')
            ->update();
        $table = $this->table('vk_ads_stats');
        $table
            ->rename('ads_stats')
            ->renameColumn('vk_ads_campaign_id', 'ads_campaign_id')
            ->update();
        $table = $this->table('vk_ads_advert');
        $table
            ->rename('ads_advert')
            ->renameColumn('vk_ads_campaign_id', 'ads_campaign_id')
            ->update();
        $table = $this->table('vk_ads_advert_stats');
        $table
            ->rename('ads_advert_stats')
            ->renameColumn('vk_ads_advert_id', 'ads_advert_id')
            ->update();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('ads_campaign');
        $table
            ->rename('vk_ads_campaign')
            ->update();
        $table = $this->table('ads_stats');
        $table
            ->rename('vk_ads_stats')
            ->renameColumn('ads_campaign_id', 'vk_ads_campaign_id')
            ->update();
        $table = $this->table('ads_advert');
        $table
            ->rename('vk_ads_advert')
            ->renameColumn('ads_campaign_id', 'vk_ads_campaign_id')
            ->update();
        $table = $this->table('ads_advert_stats');
        $table
            ->rename('vk_ads_advert_stats')
            ->renameColumn('ads_advert_id', 'vk_ads_advert_id')
            ->update();
    }
}
