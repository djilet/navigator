<?php
require_once(dirname(__FILE__) . "/../include/init.php");
set_time_limit(0);
function SpecialityAddressCordImport(){

	$stmt = GetStatement();
	$allSpecFromDB = $stmt->FetchList("SELECT SpecialityID, Address FROM data_speciality WHERE Address IS NOT NULL AND Latitude IS NULL");
	$import = new Import\SpecialityAddressImport();
	while (ob_end_clean()){};
	ob_implicit_flush(1);
	$counter = 0;
	foreach ($allSpecFromDB as $k=>$v){
		if ($v['Address'] && $v['Latitude']==NULL && $v['Longitude']==NULL){
			$counter++;
			$import->customUpdate($v, $stmt, $counter);
		}
	}
	echo '<h1>Скрипт заполнения координат у специальностей завершился успешно</h1>';
}
SpecialityAddressCordImport();