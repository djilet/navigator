<?php


use Phinx\Migration\AbstractMigration;

class Profession2Subject extends AbstractMigration
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
        $sql = "create table data_profession2subject
                (
                    ID int(10) auto_increment,
                    ProfessionID int(10) not null,
                    SubjectID int(10) not null,
                    constraint data_profession2subject_pk
                        primary key (ID)
                );";

        $this->query($sql);
    }

    public function down(){
        $sql = "drop table data_profession2subject";
        $this->query($sql);
    }
}
