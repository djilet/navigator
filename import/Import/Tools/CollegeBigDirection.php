<?php
namespace Import\Tools;
require_once(PROJECT_DIR . "/module/college/include/college_bigdirection.php");

class CollegeBigDirection extends \CollegeBigDirection {
	protected $stmt;
	protected $directions;

	public function __construct(\Statement $stmt){
		parent::LocalObjectList();
		$this->stmt = $stmt;
		$this->directions = $this->stmt->FetchIndexedAssocList(
			'SELECT `CollegeBigDirectionID`,`Title` FROM `college_bigdirection`',
			'Title'
		);
	}

	public function getId($title){
        if(empty($title)){
            //TODO error list
            return Null;
        }
		if (!isset($this->directions[$title])) {
			$this->insert($title);
		}

		return $this->directions[$title]['CollegeBigDirectionID'];
	}

	public function insert($title){
		$query = "INSERT INTO `college_bigdirection` SET
					`Title` = " . \Connection::GetSQLString($title) . ",
                    `SortOrder` = " . parent::getMaxSortOrder();
		if ($this->stmt->Execute($query)) {
			$this->directions[$title] = [
				'Title'    => $title,
				'CollegeBigDirectionID' => $this->stmt->GetLastInsertID(),
			];
		}
	}
}