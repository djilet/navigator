<?php
require_once(dirname(__FILE__)."/processor.php");
require_once(dirname(__FILE__)."/../module/tracker/include/tracker.php");

class TrackerProcessor extends Processor
{
	public function run()
	{
		if (Tracker::removeInvalidTracking()){
			//$this->logger->info('removed invalid users tracking');
			return true;
		}
	}

}