<?php


use Phinx\Migration\AbstractMigration;

class OnlineEventNewFields extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(){
        $sql = "ALTER TABLE `data_online_event` ADD `Template` ENUM('normal','career','prof') NOT NULL DEFAULT 'normal' AFTER `HeadImageConfig`, ADD `RegistrationType` ENUM('normal','student') NOT NULL DEFAULT 'normal' AFTER `Template`, ADD `ShowInList` ENUM('Y','N') NOT NULL DEFAULT 'Y' AFTER `RegistrationType`;";
        $this->query($sql);
    }

    public function down(){
        $sql = "ALTER TABLE `data_online_event` DROP `Template`,`RegistrationType`, `ShowInList`;";
        $this->query($sql);
    }
}