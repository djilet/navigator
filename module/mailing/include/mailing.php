<?php

class Mailing extends LocalObject
{
    private $module;
    
    public function __construct($module)
    {
        $this->module = $module;
    }
    
    function LoadByID($id)
    {
    	$query = "SELECT m.MailingID, m.From, m.Theme, m.Text, m.Emails, m.Time, m.Status
    		FROM `mailing_mailing` AS m
    		WHERE m.MailingID=".Connection::GetSQLString($id);
    	$this->LoadFromSQL($query);
    
    	if ($this->GetProperty("MailingID"))
    	{
    		if($this->GetProperty("Status") == 'inprogress' || $this->GetProperty("Status") == 'complete')
    		{
    			$this->SetProperty("Disabled", 1);
    		}
    		return true;
    	}
    	else
    	{
    		return false;
    	}
    }

    public function Save()
    {
    	if (!$this->ValidateNotEmpty('From')) {
    		$this->AddError('mailing-from-empty', $this->module);
    	}
        if (!$this->ValidateNotEmpty('Theme')) {
            $this->AddError('mailing-theme-empty', $this->module);
        }
        if (!$this->ValidateNotEmpty('Text')) {
            $this->AddError('mailing-text-empty', $this->module);
        }
        if (!$this->ValidateNotEmpty('Emails')) {
        	$this->AddError('mailing-emails-empty', $this->module);
        }
        else {
        	$emailList = explode(PHP_EOL, $this->GetProperty('Emails'));
        	if(count($emailList) > 1000){
        		$this->AddError('mailing-emails-many', $this->module);
        	}
        }
        if (!$this->ValidateNotEmpty('Time')) {
        	$this->AddError('mailing-time-empty', $this->module);
        }
        if (!$this->ValidateNotEmpty('Status')) {
        	$this->AddError('mailing-status-empty', $this->module);
        }
        
        if ($this->HasErrors()) {
            return false;
        }

        $stmt = GetStatement();
        
        if ($this->GetIntProperty("MailingID") > 0)
		{
			$query = "UPDATE `mailing_mailing` SET
						`From`=".$this->GetPropertyForSQL("From").", 
						`Theme`=".$this->GetPropertyForSQL("Theme").",
						`Text`=".$this->GetPropertyForSQL("Text").",
						`Emails`=".$this->GetPropertyForSQL("Emails").",
						`Time`=".Connection::GetSQLDateTime($this->GetProperty("Time")).",
						`Status`=".$this->GetPropertyForSQL("Status")." 
				WHERE `MailingID`=".$this->GetIntProperty("MailingID");
		}
		else
		{
			$query = "INSERT INTO `mailing_mailing` SET
						`From`=".$this->GetPropertyForSQL("From").", 
						`Theme`=".$this->GetPropertyForSQL("Theme").",
						`Text`=".$this->GetPropertyForSQL("Text").",
						`Emails`=".$this->GetPropertyForSQL("Emails").",
						`Time`=".Connection::GetSQLDateTime($this->GetProperty("Time")).",
						`Status`=".$this->GetPropertyForSQL("Status").",
						`Created`=".Connection::GetSQLString(date("Y-m-d H:i:s"));
		}

		if ($stmt->Execute($query))
		{
			if (!$this->GetIntProperty("MailingID") > 0)
				$this->SetProperty("MailingID", $stmt->GetLastInsertID());

			return true;
		}
		else
		{
			$this->AddError("sql-error");
			return false;
		}
    }
}
