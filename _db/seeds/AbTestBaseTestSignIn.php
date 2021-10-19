<?php


use Phinx\Seed\AbstractSeed;

class AbTestBaseTestSignIn extends AbstractSeed
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
        $sql = "INSERT INTO `abtest_test` (`Name`) VALUES ('BasetestSignIn')";
        $this->query($sql);
    }
}
