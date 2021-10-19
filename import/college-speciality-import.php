<?php
require_once(dirname(__FILE__) . "/../include/init.php");

$import = new Import\CollegeSpecialityImport();
if ($import->setImportFile(__DIR__ . '/source/college_speciality.csv') === false) {
    echo $import->getErrors();
    exit;
}

$i = 0;
while (($data = $import->getNext()) !== false) {

    if ($data[0] == 'id колледжа' || empty($data[0])) {
        continue;
    }

    if ($id = $import->findCollegeByImportID($import->value('CollegeID'))) {
        $import->setCollegeID($id);
        $import->deleteByCollegeID();
    }

    /*if ($i >= 3){
        break;
    }
    $i++;*/
}

$import->reInitSpecImportIDs();
$import->toStart();

$i = 0;
while (($data = $import->getNext()) !== false) {

    if ($data[0] == 'id колледжа') {
        continue;
    }

    if ($id = $import->findCollegeByImportID($import->value('CollegeID'))) {
        $import->setCollegeID($id);
        $import->insert();
    }

    /*if ($i >= 4){
        break;
    }
    $i++;*/
}

$import->uniqStaticPath();