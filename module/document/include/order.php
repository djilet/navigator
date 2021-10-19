<?php

class DocumentOrder extends LocalObject
{
    private $module;
    private $errorNames = array();

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function Create(LocalObject $post)
    {
    	if (!$post->ValidateNotEmpty('PageID')) {
    		$this->errorNames[] = "PageID";
    		$this->AddError('document-pageid-empty', $this->module);
    	}
    	if (!$post->ValidateNotEmpty('RegionID')) {
    		$this->errorNames[] = "RegionID";
    		$this->AddError('document-regionid-empty', $this->module);
    	}
    	if (!$post->ValidateNotEmpty('UniversityCount')) {
    		$this->errorNames[] = "UniversityCount";
    		$this->AddError('document-universitycount-empty', $this->module);
    	}
    	if (!$post->ValidateNotEmpty('Name')) {
    		$this->errorNames[] = "Name";
    		$this->AddError('document-name-empty', $this->module);
    	}
    	if (!$post->ValidateNotEmpty('Address')) {
    		$this->errorNames[] = "Address";
    		$this->AddError('document-address-empty', $this->module);
    	}
    	if (!$post->ValidateNotEmpty('Email')) {
        	$this->errorNames[] = "Email";
        	$this->AddError('document-email-empty', $this->module);
        }
        else if (!$post->ValidateEmail('Email')) {
        	$this->errorNames[] = "Email";
        	$this->AddError('document-email-incorrect', $this->module);
        }
        if (!$post->ValidateNotEmpty('Phone')) {
            $this->errorNames[] = "Phone";
            $this->AddError('document-phone-empty', $this->module);
        }
        if (!$post->ValidateNotEmpty('Date')) {
        	$this->errorNames[] = "Date";
        	$this->AddError('document-date-empty', $this->module);
        }
        if (!$post->ValidateNotEmpty('Time')) {
        	$this->errorNames[] = "Time";
        	$this->AddError('document-time-empty', $this->module);
        }
        
        $stmt = GetStatement();
        $regionInfo = $stmt->FetchRow("SELECT * FROM document_price WHERE RegionID=".$post->GetIntProperty('RegionID'));
        if(!$regionInfo){
        	$this->AddError('document-regionid-empty', $this->module);
        }
        $this->SetProperty("Price", $regionInfo["Price".$post->GetIntProperty('UniversityCount')]);
        
        if ($this->HasErrors()) {
            return false;
        }

        
        $query = "INSERT INTO document_order SET 
			PageID=".$post->GetPropertyForSQL('PageID').",
			Name=".$post->GetPropertyForSQL('Name').",
			Address=".$post->GetPropertyForSQL('Address').",
			Email=".$post->GetPropertyForSQL('Email').",
			Phone=".$post->GetPropertyForSQL('Phone').",
			Date=".$post->GetPropertyForSQL('Date').",
			Time=".$post->GetPropertyForSQL('Time').",
			RegionTitle=".Connection::GetSQLString($regionInfo['RegionTitle']).",
			UniversityCount=".$post->GetPropertyForSQL('UniversityCount').",
			Universities=".$post->GetPropertyForSQL('Universities').",
			Created=".Connection::GetSQLString(GetCurrentDateTime());
        
        $ipinfo = GetIPInfo(getClientIP());
        if($ipinfo && $ipinfo->city)
        {
        	$query .= ", City=".Connection::GetSQLString($ipinfo->city);
        }
        
        $session =& GetSession();
        $userInfo = $session->GetProperty('UserItem');
        if (!empty($userInfo['UserID'])) {
        	$query .= ", UserID=".intval($userInfo['UserID']);
        }
        if($session->GetProperty('utm_source'))
        {
        	$query .= ", utm_source=".Connection::GetSQLString($session->GetProperty('utm_source')).",
        	utm_medium=".Connection::GetSQLString($session->GetProperty('utm_medium')).",
        	utm_campaign=".Connection::GetSQLString($session->GetProperty('utm_campaign')).",
        	utm_term=".Connection::GetSQLString($session->GetProperty('utm_term')).",
        	utm_content=".Connection::GetSQLString($session->GetProperty('utm_content'));
        }
        
        if($stmt->Execute($query))
        {
        	$this->SetProperty('OrderID', $stmt->GetLastInsertID());
        	return true;
        }
        return false;
    }
    
    public function AddBill(LocalObject $post)
    {
    	$stmt = GetStatement();
    	
    	$value = $this->GetProperty("Price");
    	
    	$query="INSERT INTO `payment_bill`(Summ,Type,TypeID,Created)
    		VALUES('".floatval($value)."','document',".$this->GetIntProperty('OrderID').",".Connection::GetSQLString(GetCurrentDateTime()).")";
    	if ($stmt->Execute($query))
    	{
    		$billID = $stmt->GetLastInsertID();
    		
    		$shopID = "149836";
    		//$scid = "556139";//demomoney
    		$scid = "724199";
    		$tax = 1;
    		
    		$receipt = array(
    			"customerContact" => "+".preg_replace("/[^0-9,.]/", "", $post->GetProperty('Phone')),
    			"items" => array(
    				array(
    					"quantity" => 1,
    					"price" => array(
    						"amount" => $value
    					),
    					"tax" => $tax,
    					"text" => "Консультация"
    				)
    			)
    		);
    		$formToSubmit = "<form action=\"https://money.yandex.ru/eshop.xml\" method=\"post\">
			    <input name=\"shopId\" value=\"".$shopID."\" type=\"hidden\"/>
			    <input name=\"scid\" value=\"".$scid."\" type=\"hidden\"/>
			    <input name=\"sum\" value=\"".$value."\" type=\"hidden\">
			    <input name=\"orderNumber\" value=\"".$billID."\" type=\"hidden\"/>
			    <input name=\"ym_merchant_receipt\" value='".json_encode($receipt)."' type=\"hidden\"/>
			    <input type=\"submit\" value=\"Заплатить\"/>
			</form>";

    		return $formToSubmit;
    	}
    	else 
    	{
    		$this->AddError('document-addbill-fail', $this->module);
    		return false;
    	}
    }

    public function getErrorNames()
    {
        return $this->errorNames;
    }
}
