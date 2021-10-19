<?php

use Phinx\Migration\AbstractMigration;

class CreateVkAdsCampaignTable extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('vk_ads_campaign');
        $table->addColumn('exhibition_id', 'integer')
            ->addColumn('campaign_id', 'integer')
            ->addColumn('type', 'string', ['limit' => 100])
            ->save();
    }

    public function down()
    {

    }
}