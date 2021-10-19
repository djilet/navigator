<?php

use Phinx\Migration\AbstractMigration;

class ChangePostInDataExhibitionAction extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $this->table('data_exhibition_action')
            ->changeColumn('Post', 'text')
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('data_exhibition_action')
            ->changeColumn('Post', 'string')
            ->save();
    }
}
