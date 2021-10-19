<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

class AddCoulmnForEventRegistrations extends AbstractMigration
{
        public function up()
        {
            $this->table("event_registrations")
            ->addColumn("CRMRegistrationId", "string", ['null' => true])
            ->save();
        }
        
        public function down()
        {
            $this->table("event_registrations")
            ->removeColumn("CRMRegistrationId")
            ->save();
        }
}
