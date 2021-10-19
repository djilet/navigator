<?php
require_once(dirname(__FILE__)."/../include/init.php");
require_once(dirname(__FILE__)."/logger.php");
require_once(dirname(__FILE__)."/processor_onlineevent.php");
require_once(dirname(__FILE__)."/processor_mailing.php");
require_once(dirname(__FILE__)."/processor_document.php");
require_once(dirname(__FILE__)."/processor_tracker.php");
es_include("localpage.php");

$request = array_merge($_GET, $_POST);

$logger = new Logger();
$datetime = date('Y-m-d H:i:s',time());
$logger->info("CRON STARTED: ".$datetime);

//onlineevent-4-hour-notification
$onlineevent = new OnlineEventProcessor($logger);
$onlineevent->run();

//mailing
$mailing = new MailingProcessor($logger);
$mailing->run();

//document nitifications
$mailing = new DocumentProcessor($logger);
$mailing->run();

//tracker remove invalid user tracking
$tracker = new TrackerProcessor($logger);
$tracker->run();

$logger->info("CRON FINISHED: ".$datetime);

?>