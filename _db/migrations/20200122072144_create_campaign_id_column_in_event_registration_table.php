<?php

use Phinx\Migration\AbstractMigration;

class CreateCampaignIdColumnInEventRegistrationTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change()
    {
        $table = $this->table('event_registrations');
        $table->addColumn('CampaignId', 'string', ['after' => 'AdditionalType', 'null' => true])
            ->addColumn('AdvertId', 'string', ['after' => 'CampaignId', 'null' => true])
            ->update();
    }
}
