<?php

use Phinx\Migration\AbstractMigration;

class AddDataCityTable extends AbstractMigration
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
        $sql = "create table data_city
                (
                    ID int auto_increment,
                    RegionID int(10) UNSIGNED not null ,
                    Title varchar(255) not null,
                    StaticPath varchar(255) not null,
                    constraint data_city_pk primary key (ID)
                ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
                
                create unique index data_city_Title_uindex
                    on data_city (Title);
                    
                create unique index data_city_StaticPath_uindex
                    on data_city (StaticPath)";
        $this->query($sql);
    }

    public function down(){
        $sql = "drop table data_city";
        $this->query($sql);
    }
}
