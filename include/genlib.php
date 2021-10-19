<?php

es_include("mysqli/connection5.php");
es_include("object/session.php");
es_include("object/redis_session.php");
es_include("localobject.php");
es_include("localobjectlist.php");
es_include("language.php");
es_include("query_builder.php");

function &GetConnection()
{
	static $instance;
	if (is_null($instance))
	{
		$language =& GetLanguage();
		$instance = new Connection(GetFromConfig("Host", "mysql"), GetFromConfig("Database", "mysql"), GetFromConfig("User", "mysql"), GetFromConfig("Password", "mysql"), $language->GetMySQLEncoding());
	}
	return $instance;
}

function GetStatement()
{
	$instance = GetConnection();
	return $instance->CreateStatement(MYSQLI_ASSOC, E_USER_WARNING);
}

function &GetLanguage()
{
	static $language;
	if (is_null($language))
	{
		$language = new Language();
	}
	return $language;
}

function &GetURLParser()
{
	static $parser;
	if (is_null($parser))
	{
		$parser = new URLParser();
	}
	return $parser;
}

function GetTranslation($key, $module = null, $replacements = array())
{
	$language =& GetLanguage();

	if (is_array($module))
	{
		$replacements = $module;
		$module = null;
	}

	return $language->GetTranslation($key, $module, $replacements);
}

function &GetSession()
{
	static $session;
	if (is_null($session))
	{
		$session = new RedisSession("sm");
	}
	return $session;
}

function getReCaptcha()
{
    $recaptcha = new \ReCaptcha\ReCaptcha(GetFromConfig('RecaptchaSecret', 'google'));
    return $recaptcha->setExpectedHostname(GetFromConfig('Domain', 'server'));
}

function GetFromConfig($param, $section = "common")
{
	static $websiteConfig;

	if (is_null($websiteConfig) && defined("WEBSITE_FOLDER"))
	{
		$configFile = dirname(__FILE__)."/../website/".WEBSITE_FOLDER."/configure.ini";
		if (is_file($configFile))
			$websiteConfig = parse_ini_file($configFile, true);
	}

	if (isset($websiteConfig[$section][$param]))
		return $websiteConfig[$section][$param];
	else
		return null;
}

function LocalDate($format, $timeStamp = null)
{
	$text = array('F', 'M', 'l', 'D');
	$found = array();

	// Find text representations of week & month in date format
	for ($i = 0; $i < count($text); $i++)
	{
		$pos = strpos($format, $text[$i]);
		if ($pos !== false && substr($format, $pos - 1, 1) != "\\")
		{
			$format = str_replace($text[$i], "__\\".$text[$i]."__", $format);
			$found[] = $text[$i];
		}
	}

	if (is_null($timeStamp))
		$result = date($format);
	else
		$result = date($format, $timeStamp);

	// For found text representations replace it by correct language
	for ($i = 0; $i < count($found); $i++)
	{
		if (is_null($timeStamp))
			$textInLang = GetTranslation("date-".date($found[$i]));
		else
			$textInLang = GetTranslation("date-".date($found[$i], $timeStamp));
		$result = str_replace("__".$found[$i]."__", $textInLang, $result);
	}

	return $result;
}

function SmallString($str, $size)
{
	if (mb_strlen($str, "UTF-8") <= $size) return $str;
	return mb_substr($str, 0, $size-3, "UTF-8")."...";
}

function SendMailFromAdmin($to, $subject, $text, $attachments = array(), $from=null)
{
    $language =& GetLanguage();
    $result = false;
    $mailer = GetFromConfig("Mailer", "phpmailer");
    
    if($mailer == "unisender")
    {
        $apikey = GetFromConfig("UNISENDER_ApiKey", "phpmailer");
        $listid = GetFromConfig("UNISENDER_ListID", "phpmailer");
        
        $uni=new Unisender\ApiWrapper\UnisenderApi($apikey);
        
        $params = array(
            "email"=>$to,
            "sender_name"=>GetFromConfig("FromName"),
            "sender_email"=>($from == null) ? GetFromConfig("FromEmail") : $from,
            "subject"=>$subject,
            "body"=>$text,
            "list_id"=>$listid
        );
        $response = json_decode($uni->sendEmail($params), true);

        if ($response["error"]) {
            $errorMsg = $response["error"];
        } else {
            $result = true;
        }
    }
    else
    {
        es_include("phpmailer/phpmailer.php");
        $phpmailer = new PHPMailer();
        
        switch ($mailer)
        {
            case 'smtp':
                $phpmailer->IsSMTP();
                if (GetFromConfig("SMTP_Debug", "phpmailer"))
                {
                    $phpmailer->SMTPDebug = true;
                }
                else
                {
                    $phpmailer->SMTPDebug = false;
                }
                break;
            case 'mail':
                $phpmailer->IsMail();
                break;
            case 'sendmail':
                $phpmailer->IsSendmail();
                break;
        }
        
        $login = GetFromConfig("SMTP_Login", "phpmailer");
        $password = GetFromConfig("SMTP_Password", "phpmailer");
        $phpmailer->Host = GetFromConfig("SMTP_Host", "phpmailer");
        $phpmailer->Port = GetFromConfig("SMTP_Port", "phpmailer");
        
        if ($login && $password)
        {
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $login;
            $phpmailer->Password = $password;
        }
        else
        {
            $phpmailer->SMTPAuth = false;
        }
        
        $phpmailer->ContentType = "text/html";
        $phpmailer->CharSet = $language->GetHTMLCharset();
        
        $phpmailer->From = ($from == null) ? GetFromConfig("FromEmail") : $from;
        $phpmailer->FromName = GetFromConfig("FromName");
        $phpmailer->AddReplyTo($phpmailer->From, $phpmailer->FromName);
        $phpmailer->Subject = $subject;
        $phpmailer->Body = $text;
        $phpmailer->AddAddress($to);
        
        if (is_array($attachments) && count($attachments) > 0)
        {
            foreach ($attachments as $v)
            {
                $phpmailer->AddAttachment($v);
            }
        }

        $result = $phpmailer->Send();

        if (!$result)
        {
            $errorMsg = $phpmailer->ErrorInfo;
        }
        $phpmailer->ClearAllRecipients();
    }
	
	// Log message
    $fileName = $result ? date("Y-m-d-H-i-s") : date("Y-m-d-H-i-s")."_error";
	$fp = fopen(PROJECT_DIR."website/".WEBSITE_FOLDER."/var/mail/".$fileName.".txt", "a");
	$logMessage = "Time: ".date("d.m.Y H:i:s")."\n";
	$logMessage .= "Status: ".($result ? "success" : "failed")."\n";
	if (!$result) {
        $logMessage .= "Error: ".$errorMsg."\n";
    }
	$logMessage .= "Browser: ".$_SERVER['HTTP_USER_AGENT']."\n";
	$logMessage .= "From: ".GetFromConfig("FromEmail")."\n";
	$logMessage .= "From Name: ".GetFromConfig("FromName")."\n";
	$logMessage .= "To: ".$to."\n";
	$logMessage .= "Subject: ".$subject."\n";
	$logMessage .= "Body: ".$text."\n\n";
	fwrite($fp, $logMessage);	
	fclose($fp);

	return $result;
}

function SendSMSFromAdmin($phone, $text)
{
    $result = false;
    $link = GetFromConfig("SMS_Link", "smsrapporto");
    if( $link && !empty($phone) )
    {
        $link .= "?msisdn=".$phone."&message=".urlencode($text);
        $response = trim(file_get_contents($link));
        
        // Log message
        $fp = fopen(PROJECT_DIR."website/".WEBSITE_FOLDER."/var/sms/".date("Y-m-d-H-i-s").".txt", "a");
        $logMessage = "Time: ".date("d.m.Y H:i:s")."\n";
        if(is_numeric($response))
        {
            $logMessage .= "Status: success"."\n";
            $logMessage .= "MessageID: ".$response."\n";
            $result = true;
        }
        else
        {
            $logMessage .= "Status: failed"."\n";
            $logMessage .= "Error: ".$response."\n";
        }
        $logMessage .= "Phone: ".$phone."\n";
        $logMessage .= "Text: ".$text."\n\n";
        fwrite($fp, $logMessage);
        fclose($fp);
    }
    return $result;
}

function GetDirPrefix($langCode = DATA_LANGCODE)
{
	$language =& GetLanguage();
	if ($lng = $language->GetDataLanguageByCode($langCode))
		return PROJECT_PATH.$lng['LangDir'];
	else
		return PROJECT_PATH;
}

function GetUrlPrefix($langCode = DATA_LANGCODE, $withLangDir = true)
{
	$language =& GetLanguage();
	if ($lng = $language->GetDataLanguageByCode($langCode))
	{
		if ($withLangDir)
			return GetCurrentProtocol().$lng['HostName'].PROJECT_PATH.$lng['LangDir'];
		else
			return GetCurrentProtocol().$lng['HostName'].PROJECT_PATH;
	}
	else
	{
		return GetCurrentProtocol().$_SERVER["HTTP_HOST"].PROJECT_PATH;
	}
}

function GetCurrentProtocol()
{
    return GetFromConfig('Protocol', 'server');
}

function GetLangDir($langCode)
{
	$language =& GetLanguage();
	if ($lng = $language->GetDataLanguageByCode($langCode))
		return $lng['LangDir'];
	else
		return "";
}

function Send301($newURL)
{
	$language =& GetLanguage();
	header("Content-Type: text/html; charset=".$language->GetHTMLCharset());
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: ".$newURL);
	echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>301 Moved Permanently</title>
</head><body>
<h1>Moved Permanently</h1>
<p>The document has moved <a href=\"".$newURL."\">here</a>.</p>
<hr>
".isset($_SERVER['SERVER_SIGNATURE']) ? $_SERVER['SERVER_SIGNATURE'] : ''."</body></html>";
	exit();
}

function Send302($newURL)
{
	$language =& GetLanguage();
	header("Content-Type: text/html; charset=".$language->GetHTMLCharset());
	header("HTTP/1.1 302 Moved Temporarity");
	header("Location: ".$newURL);
	echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
	<html><head>
	<title>302 Moved Temporarity</title>
	</head><body>
	<h1>Moved Permanently</h1>
	<p>The document has moved <a href=\"".$newURL."\">here</a>.</p>
	<hr>
	".$_SERVER['SERVER_SIGNATURE']."</body></html>";
	exit();
}

function Send403()
{
	$language =& GetLanguage();
	header("Content-Type: text/html; charset=".$language->GetHTMLCharset());
	header("HTTP/1.1 403 Forbidden");

	$customFile = GetFromConfig("Error403Document");
	if (strlen($customFile) > 0 && is_file(PROJECT_DIR.$customFile))
	{
		$handle = fopen(PROJECT_DIR.$customFile, "rb");
		$contents = fread($handle, filesize(PROJECT_DIR.$customFile));
		fclose($handle);
		$contents = str_replace("%REQUEST_URI%", htmlspecialchars($_SERVER['REQUEST_URI']), $contents);
		$contents = str_replace("%SERVER_SIGNATURE%", htmlspecialchars($_SERVER['SERVER_SIGNATURE']), $contents);
		echo $contents;
	}
	else
	{
		echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>403 Forbidden</title>
</head><body>
<h1>Forbidden</h1>
<p>You don't have permission to access ".htmlspecialchars($_SERVER['REQUEST_URI'])." on this server.</p>
<hr>
".$_SERVER['SERVER_SIGNATURE']."</body></html>";
	}
	exit();
}

function Send404()
{
	$language =& GetLanguage();
	header("Content-Type: text/html; charset=".$language->GetHTMLCharset());
	header("HTTP/1.1 404 Not Found");

	$customTemplate = GetFromConfig("Error404Template");

	if (strlen($customTemplate) > 0 && is_file(PROJECT_DIR."website/".WEBSITE_FOLDER."/template/".$customTemplate))
	{
		$page = new Page();
		$header = array("MetaTitle" => "404 Page Not Found", "Page404" => "1");

		$module = new Module();
		$moduleList = $module->GetModuleList();
		for ($i = 0; $i < count($moduleList); $i++)
		{
			$data = $module->LoadForHeader($moduleList[$i]["Folder"]);
			if (is_array($data) && count($data) > 0)
			{
				// Put module data to header/footer
				$header = array_merge($header, $data);
				// Put module data to content (page.html) of the static pages
				$page->AppendFromArray($data);
			}
		}
		$publicPage = new PublicPage();
		$content = $publicPage->Load($customTemplate, $header);
		$content->LoadFromObject($page);
		$publicPage->Output($content);
		exit();		
	}
	else
	{
		echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL ".htmlspecialchars($_SERVER['REQUEST_URI'])." was not found on this server.</p>
<hr>
".$_SERVER['SERVER_SIGNATURE']."</body></html>";
	}
	exit();
}

function MultiSort($array)
{
	for ($i = 1; $i < func_num_args(); $i += 3)
	{
		$key = func_get_arg($i);
  		if (is_string($key)) $key = '"'.$key.'"';

		$order = true;
		if ($i + 1 < func_num_args())
			 $order = func_get_arg($i + 1);

		$type = 0;
		if ($i + 2 < func_num_args())
			 $type = func_get_arg($i + 2);
		switch($type)
		{
			 case 1: // Case insensitive natural.
				  $t = 'strcasecmp($a[' . $key . '], $b[' . $key . '])';
				  break;
			 case 2: // Numeric.
				  $t = '($a[' . $key . '] == $b[' . $key . ']) ? 0:(($a[' . $key . '] < $b[' . $key . ']) ? -1 : 1)';
				  break;
			 case 3: // Case sensitive string.
				  $t = 'strcmp($a[' . $key . '], $b[' . $key . '])';
				  break;
			 case 4: // Case insensitive string.
				  $t = 'strcasecmp($a[' . $key . '], $b[' . $key . '])';
				  break;
			 default: // Case sensitive natural.
				  $t = 'strnatcmp($a[' . $key . '], $b[' . $key . '])';
				  break;
		}
		usort($array, create_function('$a, $b', '; return ' . ($order ? '' : '-') . '(' . $t . ');'));
	}
	return $array;
}

function GetImageFields($prefix = '', $num)
{
	$result = array();
	for ($i = 1; $i < $num + 1; $i++)
	{
		$result[] = $prefix.$i;
		$result[] = $prefix.$i."Config";
	}
	if (count($result) > 0)
		return implode(", ", $result).", ";
	else
		return "";
}

function PrepareContentBeforeSave($content)
{
	// Replace PROJECT_PATH by <P_T_R> (no need to update content when you move site from one folder to another)
	if (strlen($content) > 0)
	{
		$content = str_replace("href=\"".PROJECT_PATH, "href=\"<P_T_R>", $content);
		$content = str_replace("href='".PROJECT_PATH, "href='<P_T_R>", $content);
		$content = str_replace("href=".PROJECT_PATH, "href=<P_T_R>", $content);

		$content = str_replace("src=\"".PROJECT_PATH, "src=\"<P_T_R>", $content);
		$content = str_replace("src='".PROJECT_PATH, "src='<P_T_R>", $content);
		$content = str_replace("src=".PROJECT_PATH, "src=<P_T_R>", $content);

		$content = str_replace("background=\"".PROJECT_PATH, "background=\"<P_T_R>", $content);
		$content = str_replace("background='".PROJECT_PATH, "background='<P_T_R>", $content);
		$content = str_replace("background=".PROJECT_PATH, "background=<P_T_R>", $content);
	}
	return $content;
}

function PrepareContentBeforeShow($content)
{
	// Replace <P_T_R> by PROJECT_PATH
	if (strlen($content) > 0)
	{
		$content = str_replace("<P_T_R>", PROJECT_PATH, $content);
	}
	return $content;
}

function LoadImageConfig($name, $folder, $configString)
{
	$imageConfig = explode(',', $configString);
	if (is_array($imageConfig) && count($imageConfig) > 0)
	{
		for ($i = 0; $i < count($imageConfig); $i++)
		{
			$data = explode('|', $imageConfig[$i]);
			if (is_array($data) && count($data) > 0)
			{
				if (isset($data[2]) && strlen($data[2]) > 0)
				{
					$params[$i] = array('Width' => 0, 'Height' => 0,
						'Resize' => 8, 'Name' => $name.$data[2], 'SourceName' => $data[2], 'Path' => '');

					$s = explode("x", $data[0]);
					if (count($s) == 2)
					{
						$params[$i]['Width'] = abs(intval($s[0]));
						$params[$i]['Height'] = abs(intval($s[1]));
					}

					// Resize way
					$params[$i]['Resize'] = abs(intval($data[1]));

					if($params[$i]['Resize'] == 13)
						$cropPart = "_#X1#_#Y1#_#X2#_#Y2#";
					else 
						$cropPart = "";
						
					$params[$i]['Path'] = PROJECT_PATH."images/".WEBSITE_FOLDER."-".$folder."-".$params[$i]['Width']."x".$params[$i]['Height'].$cropPart."_".$params[$i]['Resize']."/";
				}
			}
		}
	}
	return $params;
}

function InsertCropParams($path, $x1, $y1, $x2, $y2)
{
	$path = str_replace("#X1#", $x1, $path);
	$path = str_replace("#Y1#", $y1, $path);
	$path = str_replace("#X2#", $x2, $path);
	$path = str_replace("#Y2#", $y2, $path);
	return $path;
}

function LoadImageConfigValues($imageName, $value)
{
	$result = array();
	if(strlen($value) > 0)
	{
		$value = json_decode($value, true);
		if(!is_null($value))
		{
			foreach ($value as $k => $v)
			{
				if(is_array($v))
				{
					foreach ($v as $k2 => $v2)
					{
						$result[$imageName.$k.$k2] = $v2;
					}
				}
				else
				{
					$result[$imageName.$k] = $v;
				}
			}
		}
	}
	return $result;
}

function PrepareImagePath(&$item, $key, $imageConfig, $addPath = "", $keySuffix = "Image")
{
	$k = $key;
	$v = $imageConfig;
	
	if (isset($item[$k.$keySuffix]) && $item[$k.$keySuffix])
	{
		if(isset($item[$k.$keySuffix."Config"]))
			$imageConfigValues = LoadImageConfigValues($k.$keySuffix, $item[$k.$keySuffix."Config"]);
		else 
			$imageConfigValues = array();
		
		$item = array_merge($item, $imageConfigValues);
		
		for ($i = 0; $i < count($v); $i++)
		{
		    if($v[$i]["Resize"] == 13)
				$item[$v[$i]["Name"]."Path"] = InsertCropParams($v[$i]["Path"].$addPath, 
																isset($item[$v[$i]["Name"]."X1"]) ? intval($item[$v[$i]["Name"]."X1"]) : 0, 
																isset($item[$v[$i]["Name"]."Y1"]) ? intval($item[$v[$i]["Name"]."Y1"]) : 0,
																isset($item[$v[$i]["Name"]."X2"]) ? intval($item[$v[$i]["Name"]."X2"]) : 0,
																isset($item[$v[$i]["Name"]."Y2"]) ? intval($item[$v[$i]["Name"]."Y2"]) : 0).$item[$k.$keySuffix];
			else 	
				$item[$v[$i]["Name"]."Path"] = $v[$i]["Path"].$addPath.$item[$k.$keySuffix];
		}
	}
	for ($i = 0; $i < count($v); $i++)
	{
		$item[$v[$i]["Name"]."Width"] = $v[$i]["Width"];
		$item[$v[$i]["Name"]."Height"] = $v[$i]["Height"];
	}
}

function GetRealImageSize($resize, $origW, $origH, $dstW, $dstH)
{
	if (!($origW > 0 && $origH > 0 && $dstW > 0 && $dstH > 0))
		return array($dstW, $dstH);

	switch ($resize)
	{
		case RESIZE_PROPORTIONAL:
			if ($origW/$dstW > $origH/$dstH)
			{
				$k = $dstW/$origW;
				$dstH = round($origH*$k);
			}
			else
			{
				$k = $dstH/$origH;
				$dstW = round($origW*$k);
			}
			break;
		case RESIZE_PROPORTIONAL_FIXED_WIDTH:
			$k = $dstW/$origW;
			$dstH = round($origH*$k);
			break;
		case RESIZE_PROPORTIONAL_FIXED_HEIGHT:
			$k = $dstH/$origH;
			$dstW = round($origW*$k);
			break;
	}

	return array($dstW, $dstH);
}

function GetPageData($what)
{
	$default = array('ColorA' => '#000000', 'ColorI' => '#bcbcbc');

	$data = array(
		'page' => array('ColorA' => '#000000', 'ColorI' => '#bcbcbc'),
		'link' => array('ColorA' => '#0055ff', 'ColorI' => '#bcbcbc')
	);

	if (isset($data[$what])) return $data[$what];

	es_include('module.php');
	$module = new Module();
	$mList = $module->GetModuleList('', false, true);
	for ($i = 0; $i < count($mList); $i++)
	{
		if ($mList[$i]['Folder'] == $what)
			return $mList[$i];
	}

	return $default;
}

function GetPriority($level)
{
	switch($level)
	{
		case 1:
			$priority = 1;
			break;
		case 2:
			$priority = 0.8;
			break;
		case 3:
			$priority = 0.6;
			break;
		case 4:
			$priority = 0.4;
			break;
		default:
			$priority = 0.2;
			break;
	}
	return $priority;
}

function GetUploadMaxFileSize()
{
	$val = ini_get("upload_max_filesize");
	$val = strtolower(trim($val));
	$val = str_replace("m", " Mb", $val);
	$val = str_replace("g", " Gb", $val);
	$val = str_replace("k", " Kb", $val);

	return $val;
}


function ConvertURL2Value()
{
	$stmt = GetStatement();
	$page = new LocalObjectList();
	$page->LoadFromSQL("SELECT PageID, Config, Description FROM `page`");
	$pages = $page->GetItems();
	for ($i = 0; $i < count($pages); $i++)
	{
		$query = "UPDATE `page` SET Config=".Connection::GetSQLString(value_encode(urldecode($pages[$i]['Config'])))."
			,Description=".Connection::GetSQLString("Description=".value_encode(substr(urldecode($pages[$i]['Description']),12)))." 
			WHERE PageID=".$pages[$i]['PageID'];
		$stmt->Execute($query);		
	}
	
	$catalogItem = new LocalObjectList();
	$catalogItem->LoadFromSQL("SELECT ItemID, Description FROM `catalog_item`");
	$catalogItems = $catalogItem->GetItems();
	for ($i = 0; $i < count($catalogItems); $i++)
	{
	 	$query = "UPDATE `catalog_item` SET Description=".Connection::GetSQLString("Description=".value_encode(substr(urldecode($catalogItems[$i]['Description']),12)))." 
			WHERE ItemID=".$catalogItems[$i]['ItemID'];
		$stmt->Execute($query);		

	}

}

/**
* array_merge_recursive2()
*
* Similar to array_merge_recursive but keyed-valued are always overwritten.
* Priority goes to the 2nd array.
*
* @static yes
* @public yes
* @param $paArray1 array
* @param $paArray2 array
* @return array
*/
function array_merge_recursive2($paArray1, $paArray2)
{
   if (!is_array($paArray1) or !is_array($paArray2)) { return $paArray2; }
   foreach ($paArray2 AS $sKey2 => $sValue2)
   {
       $paArray1[$sKey2] = array_merge_recursive2(@$paArray1[$sKey2], $sValue2);
   }
   return $paArray1;
}

/**
 * @param array $array
 * @param string $prefix for keys
 * @return array|false
 */
function appendPrefixForArrayKeys(array $array, string $prefix)
{
    return array_combine(
        array_map(function($key) use ($prefix){ return $prefix.$key; }, array_keys($array)),
        $array
    );
}

function value_encode($str)
{
	$str = str_replace("=", "%3D", $str);
	$str = str_replace("&", "%26", $str);
	return $str;
}

function value_decode($str)
{
	$str = str_replace("%3D", "=", $str);
	$str = str_replace("%26", "&", $str);
	return $str;
}

function GetValidStaticPath($staticPath, $table)
{
	$stmt = GetStatement();	
	$i = 1;
	$validStaticPath = $staticPath;
	$query = "SELECT COUNT(*) FROM `" . $table . "` WHERE StaticPath=".Connection::GetSQLString($staticPath);
	while(($result = $stmt->FetchField($query)) > 0)
	{
		if($result === false || $result === null)
			break;
		$i++;
		$validStaticPath = $staticPath . "-" . $i;
		$query = "SELECT COUNT(*) FROM `" . $table . "` WHERE StaticPath=".Connection::GetSQLString($validStaticPath);
	}
	return $validStaticPath;	
}

function GetCurrentDateTime()
{
	return date("Y-m-d H:i:s");
}

function GetCurrentDate()
{
	return date("Y-m-d");
}

function GetCurrentTime()
{
	return date("H:i:s");
}

function RuToStaticPath($str)
{
	$trans = array(
			"а" => "a",
			"б" => "b",
			"в" => "v",
			"г" => "g",
			"д" => "d",
			"е" => "e",
			"ё" => "e",
			"ж" => "zh",
			"з" => "z",
			"и" => "i",
			"й" => "y",
			"к" => "k",
			"л" => "l",
			"м" => "m",
			"н" => "n",
			"о" => "o",
			"п" => "p",
			"р" => "r",
			"с" => "s",
			"т" => "t",
			"у" => "u",
			"ф" => "f",
			"х" => "kh",
			"ц" => "ts",
			"ч" => "ch",
			"ш" => "sh",
			"щ" => "shch",
			"ы" => "y",
			"э" => "e",
			"ю" => "yu",
			"я" => "ya",
			"А" => "A",
			"Б" => "B",
			"В" => "V",
			"Г" => "G",
			"Д" => "D",
			"Е" => "E",
			"Ё" => "E",
			"Ж" => "Zh",
			"З" => "Z",
			"И" => "I",
			"Й" => "Y",
			"К" => "K",
			"Л" => "L",
			"М" => "M",
			"Н" => "N",
			"О" => "O",
			"П" => "P",
			"Р" => "R",
			"С" => "S",
			"Т" => "T",
			"У" => "U",
			"Ф" => "F",
			"Х" => "Kh",
			"Ц" => "Ts",
			"Ч" => "Ch",
			"Ш" => "Sh",
			"Щ" => "Shch",
			"Ы" => "Y",
			"Э" => "E",
			"Ю" => "Yu",
			"Я" => "Ya",
			"Ъ" => "",
			"ъ" => "",
			"ь" => "",
			"Ь" => ""
	);
	$str = strtr($str, $trans);
	$str = str_replace(" - ", "-", $str);
	$str = str_replace(" ", "-", $str);
	$str = str_replace(".", "-", $str);
	$str = str_replace(",", "_", $str);
	for($i=0; $i<3; $i++)
		$str = str_replace("--", "-", $str);
	preg_match_all("/[A-Za-z0-9\._-]+/i", $str, $matches);
	$str = implode("", $matches[0]);
	return $str;
}

function get_enum_values($table, $field)
{
	$stmt = GetStatement();
	$type = $stmt->FetchRow( "SHOW COLUMNS FROM {$table} WHERE Field = '{$field}'" );
	preg_match("/^enum\(\'(.*)\'\)$/", $type['Type'], $matches);
	$enum = explode("','", $matches[1]);
	return $enum;
}


function getEnumList($table, $column)
{
	if (empty($table) OR empty($column)) {
		return array();
	}
	
	$table = filter_var($table, FILTER_SANITIZE_STRING);
	$column = filter_var($column, FILTER_SANITIZE_STRING);
	
	static $keyList = array();
	if(!empty($keyList[$table][$column])) return $keyList[$table][$column];

	$stmt = GetStatement();
	$query = "SHOW FIELDS FROM `{$table}` LIKE '{$column}'";
	$result = $stmt->FetchRow($query);
	preg_match('#^enum\((.*?)\)$#ism', $result['Type'], $matches);
	
	return str_getcsv($matches[1], ",", "'");
}

function counting($_n, $words, $onlysuffix = false) {
	if(!is_numeric($_n))
		return $_n;
	
    $n = abs(intval($_n)) % 100;
    $words = explode(',', $words);
    
    $prefix = $onlysuffix?'':$_n;

    if ($n>10 && $n<20) return $prefix.' '.$words[2];
    $n = $n % 10;
    if ($n>1 && $n<5) return $prefix.' '.$words[1];
    if ($n==1) return $prefix.' '.$words[0];
    return $prefix.' '.$words[2];
}

function GetIPInfo($ip)
{
    $curl = curl_init();
    $token = GetFromConfig('ApiKey', 'dadata');

    curl_setopt($curl, CURLOPT_URL, "https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address?ip={$ip}");
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Authorization: Token {$token}",
        "Accept: application/json",
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
    $response = json_decode(curl_exec($curl));
    curl_close($curl);

    if (!empty($response->location) && !empty($response->location->data)){
        return $response->location->data;
    }
    
    return false;
}

function getClientIP(){
    return isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];
}

function Permutation($arr) {
    if(is_array($arr)&&count($arr)>1) {
        foreach($arr as $k=>$v) {
            $answer[][]=$v;
        }
        do {
            foreach($arr as $k=>$v) {
                foreach($answer as $key=>$val) {
                    if(!in_array($v,$val)) {
                        $tmpArr[]=array_merge(array($v),$val);
                    }
                }
            }
            $answer=$tmpArr;
            unset($tmpArr);
        }while(count($answer[0])!=count($arr));
        return $answer;
    }else
        $answer=$arr;
        return $answer;
}

function MultilevelMap2ArrayList($array, $childLevel = 1) {
    $result = array();
    foreach($array as $id => $item){
        $item['ChildList'.$childLevel] = MultilevelMap2ArrayList($item['ChildList'], $childLevel + 1);
        unset($item['ChildList']);
        $result[] = $item;
    }
    return $result;
}

function getItemUrl($type, $id){
    $url = GetUrlPrefix();
    switch ($type){
        case 'article':
            $url .= 'article?ArticleID=' . $id;
            break;

        case 'university':
            $url .= 'university?universityID=' . $id;
            break;

        case 'speciality':
            $url .= 'university?specialityID=' . $id;
            break;

        case 'college':
            $url .= 'college?CollegeID=' . $id;
            break;

        case 'collegeSpeciality':
            $url .= 'college?CollegeSpecialityID=' . $id;
            break;
    }

    return $url;
}

function trimString($str, $count, $after = '...'){
    if (strlen($str) > $count){
        $trimStr = mb_substr($str, 0, $count);
        $trimStr .= $after;
        return $trimStr;
    }

    return $str;
}

function GetShortURL($longUrl){
    $data = [
        'data'=> [
            'type' => 'link',
            'attributes' => [
                'web_url' => $longUrl,
                'domain_id' => 30,
            ]
        ]
    ];

	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, 'https://to.click/api/v1/links');
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_HTTPHEADER, [
        'X-AUTH-TOKEN: zb5D5VG45ntKbaQrFQxykUTU',
        'Content-Type: application/json'
    ]);
	curl_setopt($curlObj, CURLOPT_POST, 1);
	curl_setopt($curlObj, CURLOPT_POSTFIELDS, json_encode($data));

	$response = curl_exec($curlObj);
    curl_close($curlObj);

	if ($response){
        $result = json_decode($response);
        return $result->data->attributes->full_url;
    }

	return false;
}

function FormatPhone($phone)
{
    $result = $phone;
    $result = preg_replace("/[^0-9]/", "", $result);
    if($result[0] == "8")
    {
        $result = "7".substr($result, 1);
    }
    return $result;
}

function GetVideoIdFromYouTube(string $url){
    $videoID = null;
    $pos = strrpos($url, '/');
    if($pos !== false){
        $videoID = substr($url, $pos + 1);
    }
    $pos = strrpos($url, '=');
    if($pos !== false){
        $videoID = substr($url, $pos + 1);
    }
    return $videoID;
}

function GetCoordsByAddress(string $address)
{
    $params = array(
        'apikey' => GetFromConfig('GeoCodeApiKey', 'yandex'),
        'geocode' => $address,
        'format'  => 'json',
        'results' => 1
    );
    $response = json_decode(
        file_get_contents('http://geocode-maps.yandex.ru/1.x/?' . http_build_query($params, '', '&'))
    );

    if ($response) {
        $point = $response->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos;
        if ($point != null) {
            $point = explode(" ", $point);
            return ['Latitude' => $point[1], 'Longitude' => $point[0]];
        }
    }

    return false;
}