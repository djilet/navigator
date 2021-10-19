<?php


use Phinx\Migration\AbstractMigration;

class BannerViewCount extends AbstractMigration
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
        $sql = "ALTER TABLE `banner_item` ADD `ViewCount` INT(7) NOT NULL DEFAULT '0' AFTER `ItemImage`;
                ALTER TABLE `banner_item` ADD `Name` VARCHAR(255) NOT NULL AFTER `BannerID`;";
        $this->query($sql);
    }

    public function down(){
        $sql = "ALTER TABLE `banner_item` DROP `ViewCount`;
                ALTER TABLE `banner_item` DROP `Name`;";
        $this->query($sql);
    }
}
