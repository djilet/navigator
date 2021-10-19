<?php

use Phinx\Migration\AbstractMigration;

class ChangeManualDateInDataExhibitionCity extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->table('data_exhibition_city')
            ->changeColumn('ManualDate', 'string', ['null' => true, 'default' => null])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('data_exhibition_city')
            ->changeColumn('ManualDate', 'datetime', ['null' => true, 'default' => null])
            ->save();
    }
}
