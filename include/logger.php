<?php

class Logger
{
	private $log_file = null;
	private $fp = null;

	function Logger($logFilePath)
	{
		$this->log_file = $logFilePath;
	}
	
	function info($message)
	{
		if (!$this->fp)
		{
			$this->lopen();	
		}
		fwrite($this->fp, "$message\n");
	}

	function error($message)
	{
		if (!$this->fp)
		{
			$this->lopen();
		}
		fwrite($this->fp, "ERROR: $message\n");
	}

	function status($message)
	{
		if (!$this->fp)
		{
			$this->lopen();
		}
		fwrite($this->fp, "STATUS: $message\n");
	}

	private function lopen()
	{
		$this->fp = fopen($this->log_file, 'a') or exit("Can't open {$this->log_file}!");
	}
}