<?php

use Phinx\Migration\AbstractMigration;

class CreateOpenDayToUniversity extends AbstractMigration
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
    public function up()
    {
        $sql = "CREATE TABLE `data_open_day2university` (`ID` INT(11) NOT NULL AUTO_INCREMENT , PRIMARY KEY (`ID`), `OpenDayID` INT(11) NOT NULL , `UniversityID` INT(11) NOT NULL) ENGINE = InnoDB;";
        $this->query($sql);
    }

    public function down(){
        $sql = "DROP TABLE data_open_day2university";
        $this->query($sql);
    }
}
