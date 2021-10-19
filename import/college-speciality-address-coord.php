<?php
require_once(dirname(__FILE__) . "/../include/init.php");
set_time_limit(0);
function SpecialityAddressCordImport(){

    $import = new Import\CollegeSpecialityImport();
    $stmt = GetStatement();

    for ($i=1; $i <= 4; $i++){
        $allSpecFromDB = $stmt->FetchList("SELECT CollegeSpecialityID, Address" . $i . " AS Address
      FROM college_speciality
      WHERE Address" . $i . " != '' AND Latitude" . $i . " IS NULL");
        while (ob_end_clean()){};
        ob_implicit_flush(1);
        $counter = 0;
        if ($allSpecFromDB){
            foreach ($allSpecFromDB as $k=>$v){
                if ($v['Address'] && $v['Latitude']==NULL && $v['Longitude']==NULL){
                    $counter++;
                    $import->customCoordinateUpdate($v, $stmt, $counter, $i);
                }
            }
        }
        echo '<h2>Координаты по ' . $i . ' адресу у специальностей колледжа заполнены</h2>';
    }
    echo '<h1>Скрипт заполнения координат специальностей колледжа завершился успешно</h1>';
}
SpecialityAddressCordImport();