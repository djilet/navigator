<?php

namespace Import\Tools;


class AdmissionBase{
	protected $stmt;
	protected $base;

	public function __construct(\Statement $stmt){
		$this->stmt = $stmt;
		$this->base = $this->stmt->FetchIndexedAssocList(
			'SELECT `AdmissionBaseID`,`Title` FROM `college_admission_base`',
			'Title'
		);
	}

	public function getId($title){
		if(empty($title)){
			//TODO error list
			return Null;
		}
		if (!isset($this->base[$title])) {
			$this->insert($title);
		}

		return $this->base[$title]['AdmissionBaseID'];
	}

	public function insert($title){
		$query = "INSERT INTO `college_admission_base` SET
					`Title` = " . \Connection::GetSQLString($title) . ",
                    `SortOrder` = 0";
		if ($this->stmt->Execute($query)) {
			$this->base[$title] = [
				'Title'    => $title,
				'AdmissionBaseID' => $this->stmt->GetLastInsertID(),
			];
		}
	}
}