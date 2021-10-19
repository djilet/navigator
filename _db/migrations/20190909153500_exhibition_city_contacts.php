<?php


use Phinx\Migration\AbstractMigration;

class ExhibitionCityContacts extends AbstractMigration
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
        $sql = "ALTER TABLE `data_exhibition_city` ADD `Phone` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `EmailTheme`, ADD `Email` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `Phone`;";
        $this->query($sql);
    }

    public function down(){
        $sql = "ALTER TABLE `data_exhibition_city` DROP `Phone`,`Email`;";
        $this->query($sql);
    }
}