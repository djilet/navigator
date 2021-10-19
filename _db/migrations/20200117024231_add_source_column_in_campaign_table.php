<?php

use Phinx\Migration\AbstractMigration;

class AddSourceColumnInCampaignTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change()
    {
        $table = $this->table('vk_ads_campaign');
        $table->addColumn('source', 'string', ['after' => 'type'])
            ->update();
    }
}
