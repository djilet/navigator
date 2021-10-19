<?php
require_once(dirname(__FILE__)."/../include/init.php");

$import = new Import\ListImport();
if ($import->setImportFile(__DIR__ . '/source/lists.csv') === false) {
    echo $import->getErrors();
    exit;
}

while (($data = $import->getNext()) !== false){
    if ($data[0] == 'Название'){
        continue;
    }

    $id = $import->findByTitle();
    $import->update($id);
}