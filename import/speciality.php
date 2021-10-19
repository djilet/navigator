<?php
require_once(dirname(__FILE__) . "/../include/init.php");

$import = new Import\SpecialityImport();
if ($import->setImportFile(__DIR__ . '/source/speciality_2020_2021.csv') === false) {
    echo $import->getErrors();
    exit;
}

while (($data = $import->getNext()) !== false) {

	if ($data[1] == 'id вуза' || empty($data[1])) {
        continue;
    }

    if ($id = $import->findUniversityByImportID($import->value('ИдВуза'))) {
        $import->setUniverID($id);
        $import->deleteByUniversityID();

    }

}
$import->reInitSpecImportIDs();
$import->toStart();

while (ob_end_clean()){};
ob_implicit_flush(1);

while (($data = $import->getNext()) !== false) {

	if ($data[1] == 'id вуза' || empty($data[1])) {
		continue;
	}

    if ($id = $import->findUniversityByImportID($import->value('ИдВуза'))) {
        $import->setUniverID($id);
        $import->insert();
    }

}
$import->uniqStaticPath();

echo 'Импорт завершен';