<?php


use Phinx\Seed\AbstractSeed;

class BasetestSetDefaultPageId extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $pageID = $this->fetchRow("SELECT PageID FROM `page` WHERE StaticPath = 'basetest'")['PageID'];
        $this->execute("UPDATE basetest_user SET PageID = " . intval($pageID));
    }
}
