<?php

use Phinx\Migration\AbstractMigration;

class CreateDataOnlineExhibitionParticipantTable extends AbstractMigration
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
        $sql = "CREATE TABLE `data_online_exhibition_participant` (
                `ID` int NOT NULL,
                  `OnlineExhibitionID` int NOT NULL,
                  `Title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                  `MainImage` varchar(255) DEFAULT NULL,
                  `UniversityID` int NOT NULL,
                  `YouTubeUrl` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                  `OnlineEventIDs` json NOT NULL,
                  `Description` text CHARACTER SET utf8 COLLATE utf8_general_ci,
                  `AttachmentUrl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
                  `UniversityWebsiteUrl` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                
                
                ALTER TABLE `data_online_exhibition_participant`
                  ADD PRIMARY KEY (`ID`);
                
                
                ALTER TABLE `data_online_exhibition_participant`
                  MODIFY `ID` int NOT NULL AUTO_INCREMENT;
";
        $this->query($sql);
    }

    public function down(){
        $sql = "drop table data_online_exhibition_participant";
        $this->query($sql);
    }
}