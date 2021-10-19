<?php 
class Processor
{
	protected $logger;
	public function __construct($logger)
	{
		$this->logger = $logger;
	}
	
	public function run()
	{
		return false;
	}
}
?>