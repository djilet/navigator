<?php

class ServiceOrder extends LocalObject
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
    		$this->AddError('service-pageid-empty', $this->module);
    	}
        if (!$post->ValidateNotEmpty('Phone')) {
            $this->errorNames[] = "Phone";
            $this->AddError('service-phone-empty', $this->module);
        }
        if (!$post->ValidateNotEmpty('Email')) {
        	$this->errorNames[] = "Email";
        	$this->AddError('service-email-empty', $this->module);
        }
        else if (!$post->ValidateEmail('Email')) {
        	$this->errorNames[] = "Email";
        	$this->AddError('service-email-incorrect', $this->module);
        }
        
        
        if ($this->HasErrors()) {
            return false;
        }

        $stmt = GetStatement();
        
        /*$founded = $stmt->FetchRow("SELECT o.OrderID, b.PayDate, b.BillID
        		FROM `service_order` o 
        		LEFT JOIN `payment_bill` b ON o.OrderID=b.TypeID AND b.Type='service'
        		WHERE o.PageID=".$post->GetPropertyForSQL('PageID')." AND o.Phone=".$post->GetPropertyForSQL('Phone'));
        if($founded)
        {
        	if($founded['PayDate'])
        	{
        		$this->AddError('service-exists', $this->module);
        		return false;
        	}
        	else 
        	{
        		$this->SetProperty('OrderID', $founded['OrderID']);
        		$stmt->Execute("DELETE FROM payment_bill WHERE BillID=".$founded['BillID']);
        		return true;
        	}
        }*/
        
        $query = "INSERT INTO service_order SET 
			PageID=".$post->GetPropertyForSQL('PageID').",
			Phone=".$post->GetPropertyForSQL('Phone').",
			Email=".$post->GetPropertyForSQL('Email').",
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
    	
    	$page = new Page();
    	$page->LoadByID($post->GetIntProperty('PageID'));
    	$config = $page->GetConfig();
    	$value = intval($config['PriceDiscount']);
    	
    	$query="INSERT INTO `payment_bill`(Summ,Type,TypeID,Created)
    		VALUES('".floatval($value)."','service',".$this->GetIntProperty('OrderID').",".Connection::GetSQLString(GetCurrentDateTime()).")";
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
    		$this->AddError('service-addbill-fail', $this->module);
    		return false;
    	}
    }

    public function getErrorNames()
    {
        return $this->errorNames;
    }
}
