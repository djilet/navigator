<?php


use Phinx\Migration\AbstractMigration;

class ShareCount extends AbstractMigration
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
        $this->query("
            CREATE TABLE `share_count` (
              `ID` int(11) NOT NULL,
              `ItemID` int(11) NOT NULL,
              `ItemType` enum('Article') NOT NULL,
              `ShareItem` enum('Facebook','Telegram','Vk','Whatsapp') NOT NULL,
              `Count` int(10) NOT NULL DEFAULT '0'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            
            ALTER TABLE `share_count`
              ADD PRIMARY KEY (`ID`),
              ADD UNIQUE KEY `ItemIDItemTypeShareItem` (`ItemID`,`ItemType`,`ShareItem`);
            
            ALTER TABLE `share_count`
              MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
        ");
    }

    public function down(){
        $this->query("DROP TABLE share_count");
    }
}
