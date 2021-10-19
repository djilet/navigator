<?php


use Phinx\Migration\AbstractMigration;

class ReadLate extends AbstractMigration
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
        $this->query("CREATE TABLE `read_later` ( `ID` INT NOT NULL AUTO_INCREMENT , `TargetID` INT NOT NULL , `TargetType` ENUM('email') NOT NULL DEFAULT 'email' , `Email` VARCHAR(70) NOT NULL , PRIMARY KEY (`ID`)) ENGINE = InnoDB;
                            ALTER TABLE `read_later` ADD `Name` VARCHAR(50) NULL DEFAULT NULL AFTER `TargetType`;");
    }

    public function down(){
        $this->query("DROP TABLE read_later");
    }
}
