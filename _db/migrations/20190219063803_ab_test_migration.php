<?php


use Phinx\Migration\AbstractMigration;

class AbTestMigration extends AbstractMigration
{
    public function up(){
        $sql = "
        create table abtest_test(
          TestID int(10) auto_increment,
          Name varchar(255) null,
          primary key (TestID)
        );
            
        create table abtest_test2user(
          ID int(10) auto_increment,
          UserItemID int(10) not null,
          TestID int not null,
          Variant VARCHAR(1) not null,
          primary key (ID)
        );";

        $this->query($sql);
    }

    public function down(){
        $sql = "DROP TABLE `abtest_test`, `abtest_test2user`;";

        $this->query($sql);
    }
}
