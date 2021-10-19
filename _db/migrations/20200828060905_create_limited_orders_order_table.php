<?php

use Phinx\Migration\AbstractMigration;

class CreateLimitedOrdersOrderTable extends AbstractMigration
{
    public function up()
    {
        $sql = "CREATE TABLE `limited_orders_order` (
                  `OrderID` int(10) UNSIGNED NOT NULL,
                  `PageID` int(10) UNSIGNED NOT NULL,
                  `UserID` int(10) UNSIGNED DEFAULT NULL,
                  `Created` datetime NOT NULL,
                  `FirstName` varchar(255) NOT NULL,
                  `LastName` varchar(255) DEFAULT NULL,
                  `Phone` varchar(255) NOT NULL,
                  `ContactType` enum('vk','telegram','whatsapp','call') DEFAULT NULL,
                  `ContactAdditional` varchar(255) DEFAULT NULL,
                  `UserWho` VARCHAR(10) NULL DEFAULT NULL,
                  `ClassNumber` VARCHAR(10) NULL DEFAULT NULL,
                  `City` varchar(255) DEFAULT NULL,
                  `Country` VARCHAR(255) NULL DEFAULT NULL,
                  `DateTime` datetime NOT NULL,
                  `utm_source` varchar(255) DEFAULT NULL,
                  `utm_medium` varchar(255) DEFAULT NULL,
                  `utm_campaign` varchar(255) DEFAULT NULL,
                  `utm_term` varchar(255) DEFAULT NULL,
                  `utm_content` varchar(255) DEFAULT NULL
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
                
                
                ALTER TABLE `limited_orders_order`
                  ADD PRIMARY KEY (`OrderID`);
                
                ALTER TABLE `limited_orders_order`
                  MODIFY `OrderID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;";

        $this->query($sql);
    }

    public function down(){
        $sql = "drop table limited_orders_order";
        $this->query($sql);
    }
}
