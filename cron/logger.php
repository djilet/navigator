<?php
class Logger
{
	private $log_file = '../var/log/cron.log';
	private $fp = null;
	var $buffer = "";
    
	function info($message) 
	{
	    $this->buffer.=$message."\n";
	    if (!$this->fp) $this->lopen();
    	    fwrite($this->fp, "$message\n");    
	}

	private function lopen()
	{
   	    $this->fp = fopen($this->log_file, 'a') or exit("Can't open $this->log_file!");
	}
}

?>