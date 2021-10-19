<?php
/**
 * Class UserUniversityService
 */
class UserUniversityService
{
    public static function exportToCsv(UserUniversityList $list)
    {
        ob_start();
        $f = fopen("php://output", "w");

        $row = [
            "Дата выбора",
            "Название направления",
            "Имя",
            "E-mail",
            "Телефон",
            "Статус",
            "Класс",
            "Город",
        ];
        fputcsv($f, $row, ";");

        foreach($list->GetItems() as $item)
        {
            $status = '';
            switch ($item['UserWho']){
                case 'parent':
                    $status = 'Родитель';
                    break;
                case 'child':
                    $status = 'Ученик';
                    break;
                case 'student':
                    $status = 'Студент';
                    break;
            }

            $row = array(
                $item['Created'],
                $item['SpecialityTitle'],
                $item['UserName'],
                $item['UserEmail'],
                $item['UserPhone'],
                $status,
                $item['ClassNumber'],
                $item['City'],
            );
            fputcsv($f, $row, ";");
        }

        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition: attachment;filename="requests.csv"');
        header("Content-Transfer-Encoding: binary");

        echo(ob_get_clean());
        exit();
    }
}