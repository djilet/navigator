<?php


use Phinx\Seed\AbstractSeed;

class BaseTestSeeder extends AbstractSeed
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
		$sql = "INSERT INTO `basetest_question` (`QuestionID`, `Title`, `Description`, `DataTable`, `SortOrder`) VALUES
		  (1, 'С кем хочу работать', 'Описание \"Кем хочу работать\"', 'WhoWork', 2),
		  (2, 'С чем хочу работать', 'Описание \"С чем хочу работать\"', 'WantWork', 3),
		  (3, 'Где хочу работать', 'Описание \"С чем хочу работать\"', 'Industry', 1);";

		$this->execute($sql);
    }
}
