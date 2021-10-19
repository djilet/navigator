<?php


use Phinx\Migration\AbstractMigration;

class UserEvent extends AbstractMigration
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
        $sql = "CREATE TABLE user_event ( `ID` INT(11) NOT NULL AUTO_INCREMENT , `Type` ENUM('lead_from_blog') NOT NULL , `Created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `Properties` JSON NULL DEFAULT NULL , PRIMARY KEY (`ID`)) ENGINE = InnoDB;";
        $this->query($sql);
    }

    public function down(){
        $sql = "DROP TABLE user_event";
        $this->query($sql);
    }
}
