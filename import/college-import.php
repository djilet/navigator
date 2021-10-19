<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../module/college/init.php");
es_include('filesys.php');

$import = new Import\CollegeImport();
if ($import->setImportFile(__DIR__ . '/source/college.csv') === false) {
	echo $import->getErrors();
	exit;
}

$i = 0;
while (($data = $import->getNext()) !== false) {

	if ($data[0] == 'id колледжа' || empty($data[0])) {
		/*
		 * Это строка с заголовками, пропускаем ее
		 */
		continue;
	}

	if ($id = $import->findCollegeByImportID($data[0])) {
		$import->update($id);
		print_r($data[0].' - update by import ID<br/>');
	} else {
		$id = $import->findCollegeByTitle($import->value('Title'));

		if ($id) {
			$import->update($id);
			print_r($data[0].' - update by title<br/>');
		} else {
			$import->update();
			print_r($data[0].' - insert new<br/>');
		}
	}

	/*$i++;
	if ($i>3){
		break;
	}*/

}
