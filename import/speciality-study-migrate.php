<?php
require_once(dirname(__FILE__) . "/../include/init.php");

set_time_limit(0);
$import = new Import\SpecialityImport();
$import->migrateStudy();