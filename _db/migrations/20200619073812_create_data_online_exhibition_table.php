<?php

use Phinx\Migration\AbstractMigration;

class CreateDataOnlineExhibitionTable extends AbstractMigration
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
        $sql = "CREATE TABLE `data_online_exhibition` (
                  `ID` int NOT NULL,
                  `Title` varchar(255) NOT NULL,
                  `StaticPath` varchar(255) NOT NULL,
                  `DateFrom` date NOT NULL,
                  `DateTo` date NOT NULL,
                  `Description` text
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                
                
                ALTER TABLE `data_online_exhibition`
                  ADD PRIMARY KEY (`ID`);
                
                
                ALTER TABLE `data_online_exhibition`
                  MODIFY `ID` int NOT NULL AUTO_INCREMENT;";
        $this->query($sql);
    }

    public function down(){
        $sql = "drop table data_online_exhibition";
        $this->query($sql);
    }
}
