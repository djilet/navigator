<?php

use Phinx\Migration\AbstractMigration;

class CreateAuthorTable extends AbstractMigration
{
    public function up()
    {
        $sql = "CREATE TABLE `data_author` (
              `AuthorID` int(10) NOT NULL auto_increment,
              `Title` varchar(255) NOT NULL,
              `Description` varchar(255) DEFAULT NULL,
              `AuthorImage` varchar(255) DEFAULT NULL,
              `AuthorImageConfig` text,
              `SortOrder` int(3) NOT NULL,
              primary key (AuthorID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";
        $this->query($sql);
    }
    
    public function down(){
        $sql = "drop table data_author";
        $this->query($sql);
    }
}
