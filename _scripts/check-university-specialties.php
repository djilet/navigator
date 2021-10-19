<?php
require_once dirname(__FILE__) . "/../include/init.php";

$stmt = GetStatement();

$query = "SELECT UniversityID, ShortTitle FROM data_university WHERE
                    ImportID IS NOT NULL AND
                    ImportID != '' AND
                    UniversityID > 0";

$dataUniversityArray = $stmt->FetchList($query);
?>
<style>
    table {
        border-collapse: collapse;
    }

    th,td {
        width: 475px;
        height: 30px;
        border: 2px solid black;
        text-align: center;
        font-size: 15px;
    }
</style>
<table>
    <tr>
        <th><i>Id Вуза</i></th>
        <th><i>Аббревиатура</i></th>
        <th><i>Число строк</i></th>
    </tr>
<?php
foreach ($dataUniversityArray as $dataUniversity) {

$query = "SELECT
COUNT(*) as count
FROM data_speciality
WHERE UniversityID = " . $dataUniversity['UniversityID'];

$result = $stmt->FetchRow($query);

    ?>
    <tr>
        <td><b><?=$dataUniversity["UniversityID"]?></b></td>
        <td><b><?=$dataUniversity["ShortTitle"]?></b></td>
        <td><b><?=$result["count"]?></b></td>
    </tr>
<?php
}
?>
</table>