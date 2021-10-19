<?php
require_once(dirname(__FILE__) . '/common/ProfessionCommon.php');
require_once(dirname(__FILE__) . '/Industry.php');
require_once(dirname(__FILE__) . '/Operation.php');
require_once(dirname(__FILE__) . '/WantWork.php');
require_once(dirname(__FILE__) . '/WhoWork.php');

/**
 * Class DataProfession
 *
 * For Admin interface and API
 */
class DataProfession extends LocalObject {
	use ProfessionCommon;

	private $module;

	public function __construct($module = 'data')
	{
		parent::LocalObject();
		$this->module = $module;
	}

	public function loadByID($id)
	{
		if (!$id = intval($id)) {
			return false;
		}

		$result = $this->getItemInfo($id,'', false, true);


	//Lists
		//Industry list
		$industry = new Industry();
		$industry->load();
		$result['IndustryList'] = $industry->getItems(array($result['Industry']));

		//Operation list
		$operation = new Operation();
		$operation->load();
		$result['OperationList'] = $operation->getItems($result['Operation']);

		//WhoWork list
		$whoWork = new WhoWork();
		$whoWork->load();
		$result['WhoWorkList'] = $whoWork->getItems($result['WhoWork']);

		//WantWork list
		$wantWork = new WantWork();
		$wantWork->load();
		$result['WantWorkList'] = $wantWork->getItems($result['WantWork']);

		$this->LoadFromArray($result);
	}
	
	private function validate(LocalObject $request) {
		if (!$request->ValidateNotEmpty('Title')) {
			$this->AddError('profession-save-title-empty', $this->module);
		}
		if (!$request->ValidateNotEmpty('Industry')) {
			$this->AddError('profession-save-industry-empty', $this->module);
		}
		if (!$request->ValidateNotEmpty('WageLevel') || !$request->ValidateNotEmpty('ProWageLevel')) {
			$this->AddError('profession-save-wage-level-empty', $this->module);
		}
		if (!$request->ValidateInt('WageLevel') || !$request->ValidateInt('ProWageLevel')) {
			$this->AddError('profession-save-wage-level-not-init', $this->module);
		}
		if (!$request->ValidateNotEmpty('Description')) {
			$this->AddError('profession-save-description-empty', $this->module);
		}
		if (!$request->ValidateNotEmpty('Employee')) {
			$this->AddError('profession-save-employee-empty', $this->module);
		}
		
		return !$this->HasErrors();
	}

	public function getDirectionIDs($id){
		$query = 'SELECT * FROM `data_profession2direction` WHERE `ProfessionID`='.intval($id);
		$stmt = GetStatement();

		return array_column($stmt->FetchList($query), 'DirectionID');
	}

//Save Profession
	public function save(LocalObject $request){
		if ($this->validate($request)) {
			$stmt = GetStatement();

			$professionID = $request->GetIntProperty('ProfessionID');
			$staticPath = RuToStaticPath($request->GetProperty("Title"));
			if ($professionID == 0) {
				$sortOrder = $request->GetProperty('SortOrder');
				if ($sortOrder == null AND !$sortOrder = $stmt->FetchField('SELECT MAX(`SortOrder`)+1 FROM `data_profession`')) {
					$sortOrder = 0;
				}
				$sortOrder = intval($sortOrder);

				$query = 'INSERT INTO `data_profession`(`Title`, `TitleInParentCase`, `StaticPath`, `Industry`, `WageLevel`, `Description`, `Employee`, `AreaWork`, `ProWageLevel`, `Books`, `Films`, `Schedule`, `Operation`, `WantToWork`, `WhoToWork`, `SortOrder`) VALUES(
					'.$request->GetPropertyForSQL('Title').',
					'.$request->GetPropertyForSQL('TitleInParentCase').',
					'.Connection::GetSQLString($staticPath).',
					'.$request->GetPropertyForSQL('Industry').',
					'.$request->GetPropertyForSQL('WageLevel').',
					'.$request->GetPropertyForSQL('Description').',
					'.$request->GetPropertyForSQL('Employee').',
					'.$request->GetPropertyForSQL('AreaWork').',
					'.$request->GetPropertyForSQL('ProWageLevel').',
					'.$request->GetPropertyForSQL('Books').',
					'.$request->GetPropertyForSQL('Films').',
					'.$request->GetPropertyForSQL('Schedule').',
					'.$sortOrder.'
				)';
			} else {
				$query = 'UPDATE `data_profession` SET
					       `Title` = '.$request->GetPropertyForSQL('Title').',
					       `TitleInParentCase` = '.$request->GetPropertyForSQL('TitleInParentCase').',
					  `StaticPath` = '.Connection::GetSQLString($staticPath).',
					    `Industry` = '.$request->GetIntProperty('Industry').',
					   `WageLevel` = '.$request->GetPropertyForSQL('WageLevel').',
					 `Description` = '.$request->GetPropertyForSQL('Description').',
					    `Employee` = '.$request->GetPropertyForSQL('Employee').',
					    `AreaWork` = '.$request->GetPropertyForSQL('AreaWork').',
					    `ProWageLevel` = '.$request->GetPropertyForSQL('ProWageLevel').',
					    `Books` = '.$request->GetPropertyForSQL('Books').',
					    `Films` = '.$request->GetPropertyForSQL('Films').',
					    `Schedule` = '.$request->GetPropertyForSQL('Schedule').',
					   `SortOrder` = '.$request->GetIntProperty('SortOrder').'
				WHERE
					`ProfessionID` = '.$professionID;
			}

			if ($stmt->Execute($query)) {
				if (!$professionID) $professionID = $stmt->GetLastInsertID();
				$this->SetProperty('ProfessionID', $professionID);
				$this->saveDirections($request->GetProperty('directions'));
				$this->saveOtherProfession($request->GetProperty('OtherProfession'));
				$this->saveOperations($request->GetProperty('Operation'));
				$this->saveWhoWork($request->GetProperty('WhoWork'));
				$this->saveWantWork($request->GetProperty('WantWork'));
				return true;
			}
		}

		return false;
	}

	private function saveDirections($ids){
		if (!is_array($ids)) {
			return;
		}

		$stmt = GetStatement();
		$stmt->Execute('DELETE FROM `data_profession2direction` WHERE `ProfessionID`='.$this->GetIntProperty('ProfessionID'));
		foreach ($ids as $id) {
			$query = 'INSERT INTO `data_profession2direction` VALUES('.$this->GetIntProperty('ProfessionID').', '.intval($id).')';
			$stmt->Execute($query);
		}
	}

	public function saveOtherProfession($ids){
		if (!is_array($ids)) {
			return;
		}

		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_profession2profession` WHERE `ProfessionID` = ".$this->GetIntProperty('ProfessionID'));
		foreach ($ids as $id) {
			$query = 'INSERT INTO `data_profession2profession` VALUES('.$this->GetIntProperty('ProfessionID').', '.intval($id).')';
			$stmt->Execute($query);
		}
	}

	public function saveOperations($ids){
		if (!is_array($ids)) {
			return;
		}

		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_profession2operation` WHERE `ProfessionID` = ".$this->GetIntProperty('ProfessionID'));
		foreach ($ids as $id) {
			$query = 'INSERT INTO `data_profession2operation` VALUES('.$this->GetIntProperty('ProfessionID').', '.intval($id).')';
			$stmt->Execute($query);
		}
	}

	public function saveWhoWork($ids){
		if (!is_array($ids)) {
			return;
		}

		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_profession2who` WHERE `ProfessionID` = ".$this->GetIntProperty('ProfessionID'));
		foreach ($ids as $id) {
			$query = 'INSERT INTO `data_profession2who` VALUES('.$this->GetIntProperty('ProfessionID').', '.intval($id).')';
			$stmt->Execute($query);
		}
	}

	public function saveWantWork($ids){
		if (!is_array($ids)) {
			return;
		}

		$stmt = GetStatement();
		$stmt->Execute("DELETE FROM `data_profession2want` WHERE `ProfessionID` = ".$this->GetIntProperty('ProfessionID'));
		foreach ($ids as $id) {
			$query = 'INSERT INTO `data_profession2want` VALUES('.$this->GetIntProperty('ProfessionID').', '.intval($id).')';
			$stmt->Execute($query);
		}
	}

	public function saveSubject($ids){
        if (!is_array($ids)) {
            return;
        }

        $stmt = GetStatement();
        $stmt->Execute("DELETE FROM `data_profession2subject` WHERE `ProfessionID` = ".$this->GetIntProperty('ProfessionID'));
        foreach ($ids as $id) {
            $query = 'INSERT INTO `data_profession2subject` (ProfessionID, SubjectID) VALUES('.$this->GetIntProperty('ProfessionID').', '.intval($id).')';
            $stmt->Execute($query);
        }
    }

}