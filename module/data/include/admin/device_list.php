<?php
require_once(dirname(__FILE__)."/../../init.php");
es_include("localobjectlist.php");

class DataDeviceList extends LocalObjectList
{
	var $module;

	function DataDeviceList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"deviceid_asc" => "d.DeviceID ASC",
			"deviceid_desc" => "d.DeviceID DESC",
		));
		
		$this->SetOrderBy("deviceid_asc");
		$this->SetItemsOnPage(20);
	}

	function LoadDeviceList($request, $fullList = false)
	{
		$where = array();
		$having = array();
		if($request->GetProperty("FilterDeviceID"))
			$where[] = "d.DeviceID LIKE '%".Connection::GetSQLLike($request->GetProperty("FilterDeviceID"))."%'";
		if($request->GetProperty("FilterClient"))
			$having[] = "Client=".$request->GetPropertyForSQL("FilterClient");
		
		$query = "SELECT d.DeviceID, d.Created, d.Client
					FROM `data_device` AS d 
			".(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "")." 
			".(count($having) > 0 ? " HAVING ".implode(" AND ", $having) : "");

		if($fullList == true)
			$this->SetItemsOnPage(0);
		$this->SetCurrentPage();
		$this->LoadFromSQL($query);
		$this->_PrepareContentBeforeShow();
	}

	function _PrepareContentBeforeShow()
	{
		for ($i = 0; $i < count($this->_items); $i++)
		{
		}
	}
	
	function ExportToCSV()
	{
		ob_start();
		$f = fopen("php://output", "w");
		
		$row = array("DeviceID", "Device", "Created");
		fputcsv($f, $row, ";");
		
		foreach($this->GetItems() as $device)
		{
			$row = array(
				$device["DeviceID"], 
				$device["Client"], 
				$device["Created"]
			);
			fputcsv($f, $row, ";");
		}
		
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Disposition: attachment;filename="device_list.csv"');
		header("Content-Transfer-Encoding: binary");
		
		echo(ob_get_clean());
		exit();
	}
}

?>