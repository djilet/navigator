<?php

use Phinx\Migration\AbstractMigration;

class ChangeCampaignIdColumnInCampaignTable extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $users = $this->table('vk_ads_campaign');
        $users->changeColumn('campaign_id', 'string', ['limit' => 255])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {

    }
}
