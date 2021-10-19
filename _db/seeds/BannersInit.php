<?php


use Phinx\Seed\AbstractSeed;

class BannersInit extends AbstractSeed
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
        $sql = "INSERT INTO `banner_banner` (`StaticPath`, `Name`, `RotateInterval`, `ImageConfig`, `Active`) VALUES
                ('TopSm', 'Верхний до 768px', 1, '300x35|8|Admin,767x90|0|Full', 'Y'),
                ('TopLg', 'Верхний от 768px', 1, '300x17|8|Admin,1612x90|0|Full', 'Y'),
                ('PopUp', 'Всплывающий', 1, '300x205|8|Admin,639x436|0|Full', 'Y'),
                ('Sidebar', 'Сайтбар', 1, '178x300|8|Admin,290x490|0|Full', 'Y');";
        $this->query($sql);
    }
}
