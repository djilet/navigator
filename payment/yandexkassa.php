<?php
require_once(dirname(__FILE__)."/../include/init.php");
$request = array_merge($_GET, $_POST);

$logger = new Logger();
$stmt = GetStatement();
$datetime = date(DATE_ATOM,time());

$shopId = "149836";
$shopPassword = "jufvbghjuhfvbhjdfybz";
$invoiceId = $request['invoiceId'];
$billID = $request['orderNumber'];

//check sign
$str = $request['action'] . ";" .$request['orderSumAmount'] . ";" . $request['orderSumCurrencyPaycash'] . ";" .$request['orderSumBankPaycash'] . ";" . $request['shopId'] . ";" .$request['invoiceId'] . ";" . $request['customerNumber'] . ";" . $shopPassword;
$md5 = strtoupper(md5($str));
if ($md5 == strtoupper($request['md5']))
{
	$query = "SELECT Summ FROM `payment_bill` WHERE BillID=".$billID." AND PayDate IS NULL";
	$result = $stmt->FetchList($query);
	if ($result && count($result) > 0)
	{
		if ($request['action'] == 'checkOrder')
		{
			$result_code = 0;
	
			$logger->info("CHECK_ORDER: BillID=".$billID.", InvoicelID=".$invoiceId.", Date=".$datetime);
	
			$response = '<?xml version="1.0" encoding="UTF-8"?><' . $request['action'] . 'Response performedDatetime="' . $datetime .
			'" code="' . $result_code . '"  invoiceId="' . $invoiceId . '" shopId="' . $shopId . '"/>';
	
			print($response);
		}
		else if ($request['action'] == 'paymentAviso')
		{
			$result_code = 0;
	
			$query="UPDATE `payment_bill` SET PayDate=".Connection::GetSQLString(GetCurrentDateTime())." WHERE BillID=".intval($billID);
			$stmt->Execute($query);
	
			$logger->info("PAYMENT_AVISO: BillID=".$billID.", InvoicelID=".$invoiceId.", Date=".$datetime);
	
			$response = '<?xml version="1.0" encoding="UTF-8"?><' . $request['action'] . 'Response performedDatetime="' . $datetime .
			'" code="' . $result_code . '"  invoiceId="' . $invoiceId . '" shopId="' . $shopId . '"/>';
	
			print($response);
		}
	}
	else
	{
		$result_code = 100;
	
		$logger->info("ERROR (BillID not found): BillID=".$billID.", InvoicelID=".$invoiceId.", Date=".$datetime);
	
		$response = '<?xml version="1.0" encoding="UTF-8"?><' . $request['action'] . 'Response performedDatetime="' . $datetime .
		'" code="' . $result_code . '"  invoiceId="' . $invoiceId . '" shopId="' . $shopId . '"/>';
	
		print($response);
	}
}
else 
{
	$logger->info("ERROR (Incorrect MD5): BillID=".$billID.", InvoicelID=".$invoiceId.", Date=".$datetime);
}

class Logger
{
	private $log_file = '../var/log/payment.log';
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
   	    $this->fp = fopen($this->log_file, 'a') or exit("Can't open $lfile!");
	}
}

?>