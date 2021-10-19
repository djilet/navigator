<?php
es_include('filesys.php');

class Exhibition extends LocalObject {
    
    private $module;
    protected $propertyList;

    /**
     * Exhibition constructor.
     *
     * @param $module
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    public function loadByID($exhibitionId)
    {
        $query = 'SELECT * FROM data_exhibition WHERE ExhibitionID='.intval($exhibitionId);
        $this->LoadFromSQL($query);

        //TODO one method for all exhibition models
        //Prepare properties
        $this->propertyList = new ExhibitionPropertyList();
        $this->propertyList->loadByExhibition($this->GetIntProperty('ExhibitionID'));

        foreach ($this->propertyList->GetItems() as $index => $property) {
            $this->SetProperty('Property' . $property['Property'], $property['Value']);
        }
    }

    public function save(LocalObject $request)
    {
        $id = $request->GetIntProperty('ExhibitionID');
        
        if (! $request->ValidateNotEmpty('Title')) {
            $this->AddError('exhibition-save-title-empty', $this->module);
        }
        if (! $request->ValidateNotEmpty('Type')) {
            $this->AddError('exhibition-save-type-empty', $this->module);
        }
        if (! $request->ValidateNotEmpty('DateFrom')) {
            $this->AddError('exhibition-save-date-from-empty', $this->module);
        }
        if (! $request->ValidateNotEmpty('DateTo')) {
            $this->AddError('exhibition-save-date-to-empty', $this->module);
        }
        
        if ($this->HasErrors()) {
            return false;
        }

	if(strlen($request->GetProperty('Page2ID')) == 0){
	    $request->RemoveProperty('Page2ID');
	}
        
        $stmt = GetStatement();
        if ($id > 0) {
            $query = "UPDATE `data_exhibition` SET
                   `Title` = ".$request->GetPropertyForSQL('Title').",
                  `PageID` = ".$request->GetPropertyForSQL('PageID').",
                 `Page2ID` = ".$request->GetPropertyForSQL('Page2ID').",
                    `Type` = ".$request->GetPropertyForSQL('Type').",
                `DateFrom` = ".Connection::GetSQLDate($request->GetProperty("DateFrom")).",
                  `DateTo` = ".Connection::GetSQLDate($request->GetProperty("DateTo")).",
                   `Phone` = ".$request->GetPropertyForSQL('Phone').",
                   `Email` = ".$request->GetPropertyForSQL('Email')."
                WHERE
                    `ExhibitionID` = " . $id;
        } else {
            $query = "INSERT INTO `data_exhibition` SET
                   `Title` = ".$request->GetPropertyForSQL('Title').",
                  `PageID` = ".$request->GetPropertyForSQL('PageID').",
                 `Page2ID` = ".$request->GetPropertyForSQL('Page2ID').",
                    `Type` = ".$request->GetPropertyForSQL('Type').",
                `DateFrom` = ".Connection::GetSQLDate($request->GetProperty("DateFrom")).",
                  `DateTo` = ".Connection::GetSQLDate($request->GetProperty("DateTo")).",
                   `Phone` = ".$request->GetPropertyForSQL('Phone').",
                   `Email` = ".$request->GetPropertyForSQL('Email');
        }
        $result = $stmt->Execute($query);
        
        if ($id == 0) {
            $id = $stmt->GetLastInsertID();
            $this->SetProperty('ExhibitionID', $id);
            $request->SetProperty('ExhibitionID', $id);
        }

        //TODO normal save
        $this->propertyList = new ExhibitionPropertyList();

        if ($request->IsPropertySet('Properties')){
            foreach ($request->GetProperty('Properties') as $property => $val) {
                $this->propertyList->_items[] = ['Property' => $property, 'Value' => $val];
            }
        }

        $this->propertyList->saveForExhibition($this->GetIntProperty('ExhibitionID'));
        
        return ($result and $request->GetIntProperty('ExhibitionID') > 0);
    }
}