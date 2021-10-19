<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../module/data/init.php");
es_include('filesys.php');

$fileSys = new \FileSys();
$stmt = GetStatement();

$files = glob("images/*.jpg");
foreach($files as $jpg){
	
	$tmp = explode('/', $jpg);
	$tmp = $tmp[count($tmp)-1];
	$tmp = substr($tmp, 0, strlen($tmp)-4);
	$tmp = explode('_', $tmp);
	$importID = $tmp[0];
	$sortOrder = $tmp[1];
	
	$universityID = $stmt->FetchField('SELECT UniversityID FROM data_university WHERE ImportID='.Connection::GetSQLString($importID));
	print_r($importID." ".$sortOrder." ".$universityID."<br/>");
	if($universityID)
	{
		$filename = $fileSys->GenerateUniqueName(DATA_UNIVERSITY_IMAGE_DIR, 'jpg');
		$fileSys->Move($jpg, DATA_UNIVERSITY_IMAGE_DIR.$filename);
	
		$stmt->Execute('INSERT INTO `data_university_image` SET
			`ItemImage`='.\Connection::GetSQLString($filename).',
			`UniversityID`='.intval($universityID).',
			`SortOrder`='.intval($sortOrder));
	}
	
	
}
