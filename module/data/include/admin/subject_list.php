<?php
require_once(dirname(__FILE__)."/../../init.php"); 
es_include("localobjectlist.php");

class DataSubjectList extends LocalObjectList
{
	var $module;
	
	function DataSubjectList($module, $data = array())
	{
		parent::LocalObjectList($data);

		$this->module = $module;
		$this->SetSortOrderFields(array(
			"title_asc" => "s.Title ASC",
			"title_desc" => "s.Title DESC",
		));

		$this->SetOrderBy("sortorder_asc");
	}

	function LoadSubjectList()
	{
		$query = "SELECT s.SubjectID, s.Title FROM `data_subject` AS s";
		$this->LoadFromSQL($query);
	}
}

?>