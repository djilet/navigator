<?php
require_once(dirname(__FILE__) . "/../include/init.php");

$import = new Import\SpecialityImport();
if ($import->setImportFile(__DIR__ . '/speciality-old.csv') === false) {
	echo $import->getErrors();
	exit;
}

$stmt = GetStatement();

$count = 0;
while (($data = $import->getNext()) !== false) {

	if ($data[0] == 'id вуза') {
		/*
		 * Это строка с заголовками, пропускаем ее
		*/
		continue;
	}

	if ($id = $import->findUniversityByImportID($import->value('ИдВуза'))) {
		/*
		 * id вуза в базе найдем, можем привязать к нему специальность
		*/
		$import->setUniverID($id);

		if (!$import->findSpecialityByImportID($import->value('ИдСпециальности'))) {
			$count ++;
			print_r($count." ".$import->value('ИдВуза')." ".$import->value('ИдСпециальности')." ".$import->value('Наименование')."<br>");
			$import->insert();
		}
	}
}
