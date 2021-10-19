<?php

class Limit extends LocalObject
{
    private $module;
    private $errorNames = array();

    public function __construct($module)
    {
        $this->module = $module;
    }

    function LoadByID($limitID)
    {
        $query = "SELECT *
			FROM `limited_orders_limit`
			WHERE LimitID=".Connection::GetSQLString($limitID);
        $this->LoadFromSQL($query);

        if ($this->GetProperty("LimitID"))
            return true;

        return false;
    }

    public function Save()
    {
        
        if (!$this->ValidateNotEmpty('PageID')) {
    		$this->errorNames[] = "PageID";
    		$this->AddError('limit-pageid-empty', $this->module);
    	}
        if (!$this->ValidateNotEmpty('Date')) {
            $this->errorNames[] = "Date";
            $this->AddError('limit-date-empty', $this->module);
        }
        if (!$this->ValidateNotEmpty('TimeFrom')) {
            $this->errorNames[] = "TimeFrom";
            $this->AddError('limit-time-from-empty', $this->module);
        }
        if (!$this->ValidateNotEmpty('TimeTo')) {
            $this->errorNames[] = "TimeTo";
            $this->AddError('limit-time-to-empty', $this->module);
        }

        if ($this->HasErrors()) {
            return false;
        }

        $stmt = GetStatement();

        if ($this->GetIntProperty("LimitID") > 0)
        {
            $query = "UPDATE `limited_orders_limit` SET
                PageID=".$this->GetPropertyForSQL('PageID').",
                Date=".Connection::GetSQLDate($this->GetProperty('Date')).",
                TimeFrom=".$this->GetPropertyForSQL('TimeFrom').",
                TimeTo=".$this->GetPropertyForSQL('TimeTo').",
                Step=".$this->GetPropertyForSQL('Step').",
                LimitCount=".$this->GetPropertyForSQL('LimitCount')."
				WHERE LimitID=".$this->GetIntProperty("LimitID");
        }
        else
        {
            $query = "INSERT INTO limited_orders_limit SET 
                PageID=".$this->GetPropertyForSQL('PageID').",
                Date=".Connection::GetSQLDate($this->GetProperty('Date')).",
                TimeFrom=".$this->GetPropertyForSQL('TimeFrom').",
                TimeTo=".$this->GetPropertyForSQL('TimeTo').",
                Step=".$this->GetPropertyForSQL('Step').",
                LimitCount=".$this->GetPropertyForSQL('LimitCount');
        }

        if ($stmt->Execute($query))
        {
            if (!($this->GetIntProperty("LimitID") > 0))
            {
                $this->SetProperty("LimitID", $stmt->GetLastInsertID());
            }
            return true;
        }
        else
        {
            $this->AddError("sql-error");
            return false;
        }
    }

    public function remove($limitID)
    {
        $stmt = GetStatement();

        $query = "DELETE FROM limited_orders_limit WHERE LimitID = " . $limitID;

        if($stmt->Execute($query))
            return true;

        return false;
    }
}
