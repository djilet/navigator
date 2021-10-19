<?php

use Phinx\Migration\AbstractMigration;

class AddSourceColumnInAdvertTable extends AbstractMigration
{
    /**
     * Change Method.
     */
    public function change()
    {
        $table = $this->table('vk_ads_advert');
        $table->addColumn('source', 'string', ['after' => 'city'])
            ->update();
    }
}
