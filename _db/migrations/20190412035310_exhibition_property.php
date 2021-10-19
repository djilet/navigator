<?php


use Phinx\Migration\AbstractMigration;

class ExhibitionProperty extends AbstractMigration
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
        $sql = "";
        $this->query("
        CREATE TABLE `data_exhibition_property` (
          `ID` int(11) NOT NULL,
          `ExhibitionID` int(11) NOT NULL,
          `Property` enum('HideUserTime') NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        
        ALTER TABLE `data_exhibition_property`
          ADD PRIMARY KEY (`ID`);
        
        ALTER TABLE `data_exhibition_property`
          MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
        ");
    }

    public function down(){
        $sql = "DROP TABLE data_exhibition_property";
        $this->query($sql);
    }
}
