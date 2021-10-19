<?php
require_once(dirname(__FILE__)."/../include/init.php");

$stmt = GetStatement();

$stmt->Execute('DELETE FROM proftest_user');
$stmt->Execute('DELETE FROM proftest_answer2user');
$stmt->Execute('DELETE FROM proftest_task2user');

?>