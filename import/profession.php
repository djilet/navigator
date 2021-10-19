<?php 
require_once(dirname(__FILE__) . "/../include/init.php");

$import = new Import\ProfessionImport(';', '"');

//temp
//$import->migratetIndustry();
//temp

if ($import->setImportFile(__DIR__ . '/source/profession.csv') === false) {
    exit($import->getErrors());
}
if( !$import->initImport() ){
	echo $import->getErrors();
}

?>