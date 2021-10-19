<?php


use Phinx\Migration\AbstractMigration;

class DataOpenDayReplacePageIdToStaticPath extends AbstractMigration
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
        $sql = "
            ALTER TABLE `data_open_day` DROP `PageID`, DROP `Page2ID`;
            ALTER TABLE `data_open_day` ADD `StaticPath` VARCHAR(255) NOT NULL AFTER `Title`;";
        $this->query($sql);
    }

    public function down(){
        $sql = "
            ALTER TABLE `data_open_day` DROP `StaticPath`;
            ALTER TABLE `data_open_day` ADD `PageID` int(11) DEFAULT NULL AFTER `Title`, ADD `Page2ID` int(11) DEFAULT NULL AFTER `PageID`;";
        $this->query($sql);
    }
}
