<?php


use Phinx\Migration\AbstractMigration;

class DataOpenDay extends AbstractMigration
{
    public function up(){
        $sql = "CREATE TABLE `data_open_day` (
              `ID` int(11) NOT NULL,
              `Title` varchar(255) NOT NULL,
              `CityTitle` varchar(255) NOT NULL,
              `PageID` int(11) DEFAULT NULL,
              `Page2ID` int(11) DEFAULT NULL,
              `Type` varchar(255) NOT NULL,
              `DateFrom` date NOT NULL,
              `DateTo` date NOT NULL,
              `Date` datetime DEFAULT NULL,
              `Phone` varchar(50) DEFAULT NULL,
              `Email` varchar(50) DEFAULT NULL,
              `Address` varchar(255) DEFAULT NULL,
              `Latitude` varchar(50) DEFAULT NULL,
              `Longitude` varchar(50) DEFAULT NULL,
              `InfoList` text,
              `Description` varchar(500) DEFAULT NULL,
              `TitleSchedule` varchar(255) DEFAULT NULL,
              `TitleRegister` varchar(255) DEFAULT NULL,
              `GUID` varchar(255) DEFAULT NULL,
              `Active` enum('Y','N') NOT NULL,
              `EmailTemplate` text,
              `EmailTheme` varchar(255) DEFAULT NULL,
              `SortOrder` int(11) NOT NULL DEFAULT '0'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE `data_open_day_action` (
              `ActionID` int(11) NOT NULL,
              `RoomID` int(11) NOT NULL,
              `TimeFrom` varchar(5) NOT NULL,
              `TimeTo` varchar(5) NOT NULL,
              `Type` varchar(255) NOT NULL,
              `Title` varchar(255) NOT NULL,
              `Name` varchar(255) NOT NULL,
              `Post` varchar(255) NOT NULL,
              `Description` text NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE `data_open_day_partner` (
              `ID` int(11) NOT NULL,
              `OpenDayId` int(11) NOT NULL,
              `Type` enum('main','common') NOT NULL,
              `Title` varchar(255) NOT NULL,
              `Image` varchar(255) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE `data_open_day_property` (
              `ID` int(11) NOT NULL,
              `OpenDayID` int(11) NOT NULL,
              `Property` enum('HideUserTime','HeaderLogotype') NOT NULL,
              `Value` varchar(10) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE `data_open_day_registration` (
              `RegistrationID` int(10) UNSIGNED NOT NULL,
              `BaseRegistrationID` int(10) UNSIGNED DEFAULT NULL,
              `DeviceID` varchar(255) NOT NULL,
              `UserID` int(10) UNSIGNED DEFAULT NULL,
              `EventID` int(10) UNSIGNED NOT NULL,
              `StaticPath` varchar(255) DEFAULT NULL,
              `FirstName` varchar(255) DEFAULT NULL,
              `LastName` varchar(255) DEFAULT NULL,
              `City` varchar(255) DEFAULT NULL,
              `Who` varchar(255) DEFAULT NULL,
              `Class` varchar(255) DEFAULT NULL,
              `Phone` varchar(255) DEFAULT NULL,
              `Email` varchar(255) DEFAULT NULL,
              `UserItemID` int(255) UNSIGNED DEFAULT NULL,
              `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `utm_source` varchar(255) DEFAULT NULL,
              `utm_medium` varchar(255) DEFAULT NULL,
              `utm_campaign` varchar(255) DEFAULT NULL,
              `utm_term` varchar(255) DEFAULT NULL,
              `utm_content` varchar(255) DEFAULT NULL,
              `Source` enum('website','app') DEFAULT NULL,
              `Time` varchar(10) DEFAULT NULL,
              `Interest` varchar(255) DEFAULT NULL,
              `ShortLink` varchar(255) DEFAULT NULL,
              `AdditionalBigDirection` varchar(255) DEFAULT NULL,
              `AdditionalUniversity` varchar(255) DEFAULT NULL,
              `AdditionalType` varchar(255) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE `data_open_day_room` (
              `RoomID` int(11) NOT NULL,
              `OpenDayID` int(11) NOT NULL,
              `Title` varchar(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE `data_open_day_slide` (
              `ID` int(11) NOT NULL,
              `OpenDayID` int(11) NOT NULL,
              `Type` enum('common') NOT NULL DEFAULT 'common',
              `Title` varchar(255) NOT NULL,
              `Image` varchar(255) NOT NULL,
              `SortOrder` int(11) DEFAULT '0'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            CREATE TABLE `data_open_day_visit` (
              `VisitID` int(10) UNSIGNED NOT NULL,
              `RegistrationID` int(10) UNSIGNED NOT NULL,
              `VisitTime` datetime NOT NULL,
              `LoadedTime` datetime NOT NULL,
              `ScannerUserID` int(10) UNSIGNED DEFAULT NULL,
              `ScannerOpenDayID` int(10) UNSIGNED DEFAULT NULL,
              `ScannerRoom` varchar(255) DEFAULT NULL,
              `ScannerAction` varchar(255) DEFAULT NULL,
              `ScannerUniversityID` int(10) UNSIGNED DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            
            ALTER TABLE `data_open_day`
              ADD PRIMARY KEY (`ID`);
            
            ALTER TABLE `data_open_day_action`
              ADD PRIMARY KEY (`ActionID`);
            
            ALTER TABLE `data_open_day_partner`
              ADD PRIMARY KEY (`ID`);
            
            ALTER TABLE `data_open_day_property`
              ADD PRIMARY KEY (`ID`);
            
            ALTER TABLE `data_open_day_registration`
              ADD PRIMARY KEY (`RegistrationID`),
              ADD KEY `BaseRegistrationID` (`BaseRegistrationID`);
            
            ALTER TABLE `data_open_day_room`
              ADD PRIMARY KEY (`RoomID`);
            
            ALTER TABLE `data_open_day_slide`
              ADD PRIMARY KEY (`ID`);
            
            ALTER TABLE `data_open_day_visit`
              ADD PRIMARY KEY (`VisitID`),
              ADD KEY `RegistrationID` (`RegistrationID`);
            
            
            ALTER TABLE `data_open_day`
              MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
            ALTER TABLE `data_open_day_action`
              MODIFY `ActionID` int(11) NOT NULL AUTO_INCREMENT;
            ALTER TABLE `data_open_day_partner`
              MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
            ALTER TABLE `data_open_day_property`
              MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
            ALTER TABLE `data_open_day_registration`
              MODIFY `RegistrationID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
            ALTER TABLE `data_open_day_room`
              MODIFY `RoomID` int(11) NOT NULL AUTO_INCREMENT;
            ALTER TABLE `data_open_day_slide`
              MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
            ALTER TABLE `data_open_day_visit`
              MODIFY `VisitID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;";
        $this->query($sql);
    }

    public function down(){
        $sql = "DROP TABLE `data_open_day`, `data_open_day_action`, `data_open_day_partner`, `data_open_day_property`, `data_open_day_registration`, `data_open_day_room`, `data_open_day_slide`, `data_open_day_visit`;";
        $this->query($sql);
    }
}
