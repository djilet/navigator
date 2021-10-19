<?php
namespace Import\Tools;

class CollegeAwards {
	protected $stmt;
	public $list;

	public function __construct(\Statement $stmt){
		$this->stmt = $stmt;
		$this->list = $this->stmt->FetchIndexedAssocList(
			'SELECT `AwardsID`,`Title` FROM `college_award`',
			'Title'
		);
	}

	public function getId($title){
		if (!isset($this->list[$title])) {
			$this->insert($title);
		}

		return $this->list[$title]['AwardsID'];
	}

	public function insert($title){
		$query = "INSERT INTO `college_award` SET
					`Title` = " . \Connection::GetSQLString($title);
		if ($this->stmt->Execute($query)) {
			$this->list[$title] = [
				'Title'    => $title,
				'AwardsID' => $this->stmt->GetLastInsertID(),
			];
		}
	}


}