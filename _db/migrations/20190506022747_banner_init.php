<?php


use Phinx\Migration\AbstractMigration;

class BannerInit extends AbstractMigration
{
    public function up(){
        $sql = "CREATE TABLE `banner_banner` (
                  `BannerID` int(5) NOT NULL,
                  `StaticPath` varchar(30) NOT NULL,
                  `Name` varchar(40) NOT NULL,
                  `RotateInterval` int(4) DEFAULT NULL,
                  `ImageConfig` varchar(255) NOT NULL,
                  `Active` enum('Y','N') NOT NULL DEFAULT 'Y'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                
                CREATE TABLE `banner_item` (
                  `ItemID` int(10) NOT NULL,
                  `BannerID` int(10) NOT NULL,
                  `Link` varchar(255) NOT NULL,
                  `ItemImage` varchar(255) DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                
                
                ALTER TABLE `banner_banner`
                  ADD PRIMARY KEY (`BannerID`);
                
                ALTER TABLE `banner_item`
                  ADD PRIMARY KEY (`ItemID`);
                
                
                ALTER TABLE `banner_banner`
                  MODIFY `BannerID` int(5) NOT NULL AUTO_INCREMENT;
                ALTER TABLE `banner_item`
                  MODIFY `ItemID` int(10) NOT NULL AUTO_INCREMENT;";
        $this->query($sql);
    }

    public function down(){
        $sql = "DROP TABLE `banner_banner`, `banner_item`;";
        $this->query($sql);
    }
}
