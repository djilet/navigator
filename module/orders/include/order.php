<?php

class Order extends LocalObject
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
    		$this->AddError('order-pageid-empty', $this->module);
    	}
        if (!$post->ValidateNotEmpty('FirstName')) {
            $this->errorNames[] = "FirstName";
            $this->AddError('order-name-empty', $this->module);
        }
        if (!$post->ValidateNotEmpty('Phone')) {
            $this->errorNames[] = "Phone";
            $this->AddError('order-phone-empty', $this->module);
        }
        /*if (!$post->ValidateNotEmpty('ContactType')) {
            $this->errorNames[] = "ContactType";
            $this->AddError('order-contacttype-empty', $this->module);
        }*/
        
        if ($this->HasErrors()) {
            return false;
        }

        $stmt = GetStatement();
        
        $founded = $stmt->FetchField("SELECT count(*) FROM orders_order WHERE PageID=".$post->GetPropertyForSQL('PageID')." AND Phone=".$post->GetPropertyForSQL('Phone'));
        if($founded > 0)
        {
        	$this->AddError('order-exists', $this->module);
        	return false;
        }
        
        $query = "INSERT INTO orders_order SET 
			PageID=".$post->GetPropertyForSQL('PageID').",
			FirstName=".$post->GetPropertyForSQL('FirstName').",
			LastName=".$post->GetPropertyForSQL('LastName').",
			Phone=".$post->GetPropertyForSQL('Phone').",
			ContactType=".$post->GetPropertyForSQL('ContactType').",
			ContactAdditional=".$post->GetPropertyForSQL('ContactAdditional').",
			UserWho=".$post->GetPropertyForSQL('UserWho').",
			ClassNumber=".$post->GetPropertyForSQL('ClassNumber').",
			Created=".Connection::GetSQLString(GetCurrentDateTime());
        
        $ipinfo = GetIPInfo(getClientIP());
        if($ipinfo && $ipinfo->city)
        {
        	$query .= ", City=".Connection::GetSQLString($ipinfo->city);
        }
        
        if($post->IsPropertySet('Country'))
        {
            $query .= ", Country=".Connection::GetSQLString(implode(', ', $post->GetProperty('Country')));
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
        	//email notification to user
        	$page = new Page();
        	$page->LoadByID($post->GetProperty('PageID'));
        	$template = new Page();
			if($template->LoadByStaticPath("order-admin-notification"))
			{
				$content = $template->GetProperty("Content");
				$content = str_replace("[Title]", $page->GetProperty("Title"), $content);
				$content = str_replace("[FirstName]", $post->GetProperty('FirstName'), $content);
				$content = str_replace("[LastName]", $post->GetProperty('LastName'), $content);
				$content = str_replace("[Phone]", $post->GetProperty('Phone'), $content);
				$content = str_replace("[ContactType]", $post->GetProperty('ContactType'), $content);
				$content = str_replace("[ContactAdditional]", $post->GetProperty('ContactAdditional'), $content);
				$content = str_replace("[UserWho]", $post->GetProperty('UserWho'), $content);
				$content = str_replace("[ClassNumber]", $post->GetProperty('ClassNumber'), $content);
				$content = str_replace("[Created]", GetCurrentDateTime(), $content);
				SendMailFromAdmin("alexandr.oshcipov@maximumtest.ru", "Навигатор поступления: новая заявка на консультацию", $content);
				//SendMailFromAdmin("anastasiya.khan@propostuplenie.ru", "Навигатор поступления: новая заявка на консультацию", $content);
				//SendMailFromAdmin("anastasia.plakhina@propostuplenie.ru", "Навигатор поступления: новая заявка на консультацию", $content);
			}

            $query = "SELECT * FROM orders_order WHERE OrderID=" . $stmt->GetLastInsertID();
            $order = $stmt->FetchRow($query);

            if ($order) {
                $config = $page->GetConfig();
                if ($config['SendToZapier']){
                    $this->sendZapier($page, $post, $ipinfo);
                }
                if ($config['SendToCRM'] && !empty($config['CampaignGUID'])) {
                    $this->sendCRM($order, $config['CampaignGUID']);
                }
            }

			return true;
        }
        return false;
    }

    public function getErrorNames()
    {
        return $this->errorNames;
    }

    protected function sendZapier(&$page, &$post, $ipinfo){
        //TEMP zapier
        $hookUrl = 'https://hooks.zapier.com/hooks/catch/4516517/voklpu/';
        $companyId = 'CMP-01456-M7D2W';

        $params = [
            'Title' => $page->GetProperty('Title'),
            'FirstName' => $post->GetProperty('FirstName'),
            'LastName' => $post->GetProperty('LastName'),
            'Phone' => $post->GetProperty('Phone'),
            'UserWho' => $post->GetProperty('UserWho'),
            'ClassNumber' => $post->GetProperty('ClassNumber'),
            'utm_source' => 'navigator',
            'form_name' => $companyId,
        ];

        if($ipinfo && $ipinfo->city)
        {
            $params['City'] = $ipinfo->city;
        }

        $ch = curl_init($hookUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($params)))
        );

        $response = curl_exec($ch);
        //TEMP zapier END
    }

    protected function sendCRM($order, $campaignGUID){
        $hookUrl = 'https://prod-135.westeurope.logic.azure.com:443/workflows/29641e0bf2ee4c49ab48e2a28d18ce6f/triggers/manual/paths/invoke?api-version=2016-06-01&sp=%2Ftriggers%2Fmanual%2Frun&sv=1.0&sig=UHpuIS1TgstWUOKhe29IspQwJLlf034QloaFVkZmndA';

        $params = [
            'CRMName' => $order['LastName'] . ' ' . $order['FirstName'],
            'CRMPhone' => $order['Phone'],
            'CRMEmail' => '',
            'CRMType' => $order['UserWho'] === 'parent' ? 'Родитель' : 'Школьник',
            'CRMCity' => $order['City'],
            'CRMYear' => $order['ClassNumber'],
            'CRMComment' => 'консультация',
            'formname' => $campaignGUID,
            'utm_source' => $order['utm_source'],
            'utm_medium' => $order['utm_medium'],
            'utm_campaign' => $order['utm_campaign'],
        ];

        $logFile = fopen(PROJECT_DIR . 'var/log/crm.log', 'a+');

        $curlInit = curl_init($hookUrl);
        curl_setopt($curlInit, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curlInit, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curlInit, CURLOPT_VERBOSE, true);
        curl_setopt($curlInit, CURLOPT_STDERR, $logFile);

        fwrite($logFile, date('Y-m-d H:i:s') . ' START REQUEST WITH PARAMETERS:' . PHP_EOL .
            json_encode($params, JSON_UNESCAPED_UNICODE) . PHP_EOL);
        curl_exec($curlInit);
        fwrite($logFile, date('Y-m-d H:i:s') . ' FINISH REQUEST' . PHP_EOL);
        curl_close ($curlInit);
    }
}
