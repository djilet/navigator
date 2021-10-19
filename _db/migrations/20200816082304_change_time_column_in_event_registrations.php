<?php

use Phinx\Migration\AbstractMigration;

class ChangeTimeColumnInEventRegistrations extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->table('event_registrations')
            ->changeColumn('Time', 'string', ['null' => true, 'default' => null, 'limit' => 16])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('event_registrations')
            ->changeColumn('Time', 'string', ['null' => true, 'default' => null, 'limit' => 10])
            ->save();
    }
}
