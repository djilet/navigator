<?php

use Phinx\Migration\AbstractMigration;

class CreateDataOnlineEventLinkTable extends AbstractMigration
{
    public function change()
    {
        $this->table('data_online_event_link', ['id' => 'LinkID'])
            ->addColumn('OnlineEventID', 'integer', ['signed' => false])
            ->addColumn('Title', 'string')
            ->addColumn('URL', 'string')
            ->addColumn('Blank', 'enum', ['values' => ['Y', 'N'], 'default' => 'N'])
            ->addColumn('Active', 'enum', ['values' => ['Y', 'N'], 'default' => 'N'])
            ->addForeignKey('OnlineEventID', 'data_online_event', 'OnlineEventID', ['delete' => 'CASCADE', 'update' => "CASCADE"])
            ->create();
    }
}
