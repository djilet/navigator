<?php
require_once(dirname(__FILE__)."/../include/init.php");

$stmt = GetStatement();

$stmt->Execute('DELETE FROM marathon_map2user');
$stmt->Execute('DELETE FROM marathon_map2user_answer');
$stmt->Execute('DELETE FROM marathon_stage_part2user');
$stmt->Execute('DELETE FROM marathon_stage_part_task2user');
$stmt->Execute('DELETE FROM marathon_user');
$stmt->Execute('DELETE FROM marathon_user_info');
$stmt->Execute('DELETE FROM marathon_user_info_values');

?>