<?php

use Phinx\Migration\AbstractMigration;

class CreateLimitedOrdersLimitTable extends AbstractMigration
{
    public function up()
    {
        $sql = "CREATE TABLE `limited_orders_limit` (
                  `LimitID` int(10) UNSIGNED NOT NULL,
                  `PageID` int(10) UNSIGNED NOT NULL,
                  `Date` date NOT NULL,
                  `TimeFrom` varchar(5) NOT NULL,
                  `TimeTo` varchar(5) NOT NULL,
                  `Step` int(10) NOT NULL,
                  `LimitCount` int(10) DEFAULT NULL
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
                
                ALTER TABLE `limited_orders_limit`
                  ADD PRIMARY KEY (`LimitID`);
                
                ALTER TABLE `limited_orders_limit`
                  MODIFY `LimitID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;";

        $this->query($sql);
    }

    public function down(){
        $sql = "drop table limited_orders_limit";
        $this->query($sql);
    }
}
