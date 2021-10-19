<?php
//sleep(20);
function query($method, $url, $data = null)
{
    $query_data = "";

    $curlOptions = array(
        CURLOPT_RETURNTRANSFER => true
    );

    if($method == "POST")
    {
        $curlOptions[CURLOPT_POST] = true;
        $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($data);
    }
    elseif(!empty($data))
    {
        $url .= strpos($url, "?") > 0 ? "&" : "?";
        $url .= http_build_query($data);
    }
    //echo "url<pre>"; print_r($url); echo "</pre>url</br>";
    //echo "data<pre>"; print_r($data); echo "</pre>data</br>";
    $curl = curl_init($url);
    curl_setopt_array($curl, $curlOptions);
    $result = curl_exec($curl);

    return json_decode($result, 1);
}
/**
 * Вызов метода REST.
 *
 * @param string $domain портал
 * @param string $method вызываемый метод
 * @param array $params параметры вызова метода
 *
 * @return array
 
 https://propostuplenie.ru/apps/maximum/lead.php?ID=33472
 */


function api_call($method, $params)
{
    if (!defined("WEBHOOK_INP_URL")){
        dbg("Входящий вебхук не определен, завершаем.");
        die;
    }

    $domain = WEBHOOK_INP_URL;

    return query("POST", /*PROTOCOL."://".$domain."/rest/"*/
        $domain."/".$method, $params);
}



function dbg($msg, $is_append = true)
{

    $g_dbg_level=1;
    if (defined("DBG_LEVEL") && (DBG_LEVEL == 1 || DBG_LEVEL == 0)){
        $g_dbg_level = DBG_LEVEL;
    } else
        $g_dbg_level=1;

    if (defined("DBG_FORCE_APPEND")){
        if (DBG_FORCE_APPEND == 1)
            $is_append = true;
    }

    $fileName = "log0.log";

    if (isset($GLOBALS['g_log_file'])){
        $fileName = $GLOBALS['g_log_file'];
    }

    $filePath = $fileName;
    if (isset($GLOBALS['g_client_dir'])){
        $filePath = $GLOBALS['g_client_dir']."/$fileName";
    }

    if (is_array($msg)){
        $str = var_export($msg,1)."\n";
    } else $str = $msg."\n";

    if ($g_dbg_level==1) {
        //echo $str."<br/>";
	//echo "str<pre>"; print_r($str); echo "</pre>str</br>";

        if ($is_append){
            file_put_contents($filePath, $str, FILE_APPEND);
        } else {
            file_put_contents($filePath, $str);
        }
    }
}

date_default_timezone_set("Europe/Moscow");
// URL входящего вебхука
define("WEBHOOK_INP_URL", "https://maximumabroad.bitrix24.ru/rest/14/jmtpfghp59yqv2l2/");
// наш секрет для вызова хука
define("WEBHOOK_SECRET", "jmtpfghp59yqv2l2");

// код для сверки источника запроса для вебхука
define("APPLICATION_TOKEN_WEB2lead", "smevwc4gkmc3qmig");
//define("WEB_SITE_SOURCE_ID_ID", "WEB"); // источник - веб-сайт

	$lead_id = $_REQUEST['ID'];

	if(!$lead_id){
		$lead_id = $_REQUEST['data']['FIELDS']['ID'];
	}

	if(!$lead_id){
		$lead_id = $_REQUEST['data']['ID'];
	}

	if($lead_id){
		$lead = api_call("crm.lead.get", array("id" => $lead_id));
		if($lead['result']){
			$lead = $lead['result']; 
		}
	}

//echo "lead<pre>"; print_r($lead); echo "</pre>lead</br>";

//COOKIES

	$lead_cookies = $lead['UF_CRM_COOKIES'];

//echo "lead_cookies<pre>"; print_r($lead_cookies); echo "</pre>lead_cookies</br>";


	$lead_cookies_all = explode(';', $lead_cookies);
	
//echo "lead_cookies_all<pre>"; print_r($lead_cookies_all); echo "</pre>lead_cookies_all</br>";


	$lead_cookies_ga = explode('_ga=', $lead_cookies);
	$lead_cookies_ga = explode(';', $lead_cookies_ga[1]);
	$lead_cookies_ga = $lead_cookies_ga[0];

//echo "lead_cookies_ga<pre>"; print_r($lead_cookies_ga); echo "</pre>lead_cookies_ga</br>";

	$lead_cookies_fbp = explode('_fbp=', $lead_cookies);
	$lead_cookies_fbp = explode(';', $lead_cookies_fbp[1]);
	$lead_cookies_fbp = $lead_cookies_fbp[0];

//echo "lead_cookies_fbp<pre>"; print_r($lead_cookies_fbp); echo "</pre>lead_cookies_fbp</br>";

	$lead_cookies_uid = explode('_ym_uid=', $lead_cookies);
	$lead_cookies_uid = explode(';', $lead_cookies_uid[1]);
	$lead_cookies_uid = $lead_cookies_uid[0];

//echo "lead_cookies_uid<pre>"; print_r($lead_cookies_uid); echo "</pre>lead_cookies_uid</br>";

	$conversion = $lead_cookies_ga.';'.$lead_cookies_fbp.';'.$lead_cookies_uid;

//echo "conversion<pre>"; print_r($conversion); echo "</pre>conversion</br>";

	
	
	if($lead_cookies){	
		$lead_update = api_call("crm.lead.update", array(
			 "id" => $lead_id,
			 "fields" => [
				"UF_CRM_FX_CONVERSION" => $conversion,
				]
			));
	

//echo "lead_update<pre>"; print_r($lead_update); echo "</pre>lead_update</br>";



	ob_start();
	echo "lead_id<pre>\n\n"; print_r($lead_id); echo "\n\n</pre>lead_id\n\n";
	echo "lead_cookies_all<pre>\n\n"; print_r($lead_cookies_all); echo "\n\n</pre>lead_cookies_all\n\n";
	echo "lead_cookies<pre>\n\n"; print_r($lead_cookies); echo "\n\n</pre>lead_cookies\n\n";
	echo "lead_cookies_ga<pre>\n\n"; print_r($lead_cookies_ga); echo "\n\n</pre>lead_cookies_ga\n\n";
	echo "lead_cookies_fbp<pre>\n\n"; print_r($lead_cookies_fbp); echo "\n\n</pre>lead_cookies_fbp\n\n";
	echo "lead_cookies_uid<pre>\n\n"; print_r($lead_cookies_uid); echo "\n\n</pre>lead_cookies_uid\n\n";
	echo "conversion<pre>\n\n"; print_r($conversion); echo "\n\n</pre>conversion</br>";
	
	$res = ob_get_contents();
	ob_end_clean();
	file_put_contents('https://propostuplenie.ru/apps/maximum/log/'.$lead_id.'_lead_id_'.time().'.txt',$res);
	
	};

?>