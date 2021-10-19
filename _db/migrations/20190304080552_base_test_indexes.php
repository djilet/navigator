<?php


use Phinx\Migration\AbstractMigration;

class BaseTestIndexes extends AbstractMigration
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
        $sql = "ALTER TABLE `basetest_result` ADD UNIQUE `TestUserID_Question_ID` (`BaseTestUserID`, `QuestionID`);
                ALTER TABLE `basetest_result_answers` ADD UNIQUE `QuestionResultID_ItemID` (`QuestionResultID`, `ItemID`);";
        $this->query($sql);
    }

    public function down(){
        $sql = "ALTER TABLE `basetest_result` DROP INDEX `TestUserID_Question_ID`;
                ALTER TABLE `basetest_result_answers` DROP INDEX `QuestionResultID_ItemID`;";
        $this->query($sql);
    }
}
