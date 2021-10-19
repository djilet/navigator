<?php
require_once(dirname(__FILE__)."/../include/mysqli/connection5.php");
function &GetConnection()
{
	static $instance;
	if (is_null($instance))
	{
		$instance = new Connection(GetFromConfig("Host", "mysql"), GetFromConfig("Database", "mysql"), GetFromConfig("User", "mysql"), GetFromConfig("Password", "mysql"));
	}
	return $instance;
}
function GetFromConfig($param, $section = "common")
{
	static $websiteConfig;

	if (is_null($websiteConfig))
	{
		$configFile = dirname(__FILE__)."/../website/navigator/configure.ini";
		if (is_file($configFile))
			$websiteConfig = parse_ini_file($configFile, true);
	}

	if (isset($websiteConfig[$section][$param]))
		return $websiteConfig[$section][$param];
	else
		return null;
}
function GetStatement()
{
	$instance = GetConnection();
	return $instance->CreateStatement(MYSQLI_ASSOC, E_USER_WARNING);
}
function &GetGroupUsers()
{
	static $groupUsers;
	if (is_null($groupUsers))
	{
		$groupUsers = array();
	}
	return $groupUsers;
}
function PrepareText($string)
{
	$url = '@(http(s)?)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
	$string = preg_replace($url, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $string);
	return $string;
}

if (GetFromConfig('ChatSSL', 'chat'))
{
    $ws = new swoole_websocket_server('0.0.0.0', GetFromConfig('ChatPort', 'chat'), SWOOLE_BASE, SWOOLE_SOCK_TCP | SWOOLE_SSL);
    $ws->set([
        'ssl_cert_file' => GetFromConfig('ChatSSLCertFile', 'chat'),
        'ssl_key_file' => GetFromConfig('ChatSSLKeyFile', 'chat'),
        'worker_num' => 1,
    ]);
}
else
{
    $ws = new swoole_websocket_server('0.0.0.0', GetFromConfig('ChatPort', 'chat'));
}

$ws->on('open', function ($ws, $request) {
    $frameID = $request->fd;
    $groupID = $request->get["GroupID"];
    $userID = $request->get["UserID"];
    if($frameID && $groupID && $userID)
    {
    	$stmt = GetStatement();
    	//check if group available
    	$query = "SELECT COUNT(*) FROM chat_group WHERE GroupID=".intval($groupID);
    	if($stmt->FetchField($query))
    	{
    		$stmt->Execute("DELETE FROM chat_group_user WHERE FrameID=".intval($frameID));
    		$stmt->Execute("INSERT INTO chat_group_user(FrameID, GroupID, UserID) VALUES(".intval($frameID).",".intval($groupID).",".intval($userID).")");
    		
    		//send all history on connect
    		$messages = array();
    		$query = "SELECT m.MessageID, m.UserID, m.Message, m.Created, u.UserName
    			FROM chat_message m 
    			LEFT JOIN users_item u ON m.UserID=u.UserID
    			WHERE m.GroupID=".intval($groupID)." ORDER BY m.Created ASC";
    		$items = $stmt->FetchList($query);
    		for($i=0; $i<count($items); $i++)
    		{
    			$message = array("action" => "init",
    					"id" => $items[$i]["MessageID"],
    					"text" => PrepareText($items[$i]["Message"]),
    					"user" => $items[$i]["UserName"],
    					"created" => $items[$i]["Created"]);
    			$messages[] = $message;
    		}
    		$ws->push($frameID, json_encode($messages));
    	}
    }
    echo "Open: {$frameID}\n";
});

$ws->on('message', function ($ws, $frame) {
    $stmt = GetStatement();
    $now = date("Y-m-d H:i:s");
    $query = "SELECT gu.GroupID, gu.UserID, u.UserName, u.ChatStatus, IF(u.ChatLimitDate < ".Connection::GetSQLString($now).", 1 ,0) as ChatLimitExpired
    	FROM chat_group_user gu 
    	LEFT JOIN users_item u ON gu.UserID=u.UserID
    	WHERE gu.FrameID=".intval($frame->fd);
    
    $messageInfo = $stmt->FetchRow($query);
    if($messageInfo)
    {
    	$available = true;
    	if($messageInfo["ChatStatus"] == 'locked'){
    		$available = false;
    		if($messageInfo["ChatLimitExpired"]) {
    			$stmt->Execute("UPDATE users_item u SET u.ChatStatus='simple',u.ChatLimitDate=NULL WHERE u.UserID=".$messageInfo["UserID"]);
    			$available = true;
    		}
    	}
    	
    	$request = json_decode($frame->data, true);
    	if($request["action"] == "add")
    	{
    		if($available)
    		{
    			$text = $request["text"];
    			if($stmt->Execute("INSERT INTO chat_message(GroupID, UserID, Message, Created)
    					VALUES(".$messageInfo["GroupID"].",".$messageInfo["UserID"].",".Connection::GetSQLString($text).",".Connection::GetSQLString($now).")"))
    			{
    				$messages = array();
    				$messageID = $stmt->GetLastInsertID();
    				$message = array("action" => "add",
    					"id" => $messageID,
    					"text" => PrepareText($text),
    					"user" => $messageInfo["UserName"],
    					"created" => $now);
    				$messages[] = $message;
    				$query = "SELECT FrameID FROM chat_group_user WHERE GroupID=".$messageInfo["GroupID"];
    				$items = $stmt->FetchList($query);
    				for($i=0; $i<count($items); $i++)
    				{
    					$ws->push($items[$i]["FrameID"], json_encode($messages));
    				}
    			}
    		}
    		else 
    		{
    			$messages = array(array("action" => "add",
    				"id" => 0,
    				"text" => "-- Вам запрещено писать сообщения в чат --",
    				"user" => "Администратор",
    				"created" => $now));
    			$ws->push($frame->fd, json_encode($messages));
    		}
    	}
    	elseif($request["action"] == "remove" && $messageInfo["ChatStatus"] == 'moderator')
    	{
    		$messageID = $request["messageID"];
    		if($stmt->Execute("DELETE FROM chat_message WHERE MessageID=".intval($messageID)))
    		{
    			$messages = array();
    			$message = array("action" => "remove",
    				"id" => $messageID);
    			$messages[] = $message;
    			$query = "SELECT FrameID FROM chat_group_user WHERE GroupID=".$messageInfo["GroupID"];
    			$items = $stmt->FetchList($query);
    			for($i=0; $i<count($items); $i++)
    			{
    				$ws->push($items[$i]["FrameID"], json_encode($messages));
    			}
    		}
    	}
    	elseif($request["action"] == "ban" && $messageInfo["ChatStatus"] == 'moderator')
    	{
    		$messageID = $request["messageID"];
    		$userID = $stmt->FetchField("SELECT UserID FROM chat_message WHERE MessageID=".intval($messageID));
    		if($userID)
    		{
    			$limitDate = "NULL";
    			$limitDateInfo = "навсегда";
    			if($request["time"] == "day")
    			{
    				$date = strtotime("+1 day");
    				$dateStr = date("Y-m-d H:i:s", $date);
    				$limitDate = Connection::GetSQLString($dateStr);
    				$limitDateInfo = "до ".$dateStr;
    			}
    			elseif($request["time"] == "week")
    			{
    				$date = strtotime("+7 day");
    				$dateStr = date("Y-m-d H:i:s", $date);
    				$limitDate = Connection::GetSQLString($dateStr);
    				$limitDateInfo = "до ".$dateStr;
    			}
    			
    			if($stmt->Execute("UPDATE users_item SET ChatStatus='locked',ChatLimitDate=".$limitDate." WHERE UserID=".intval($userID)))
    			{
    				$messages = array(array("action" => "info",
    					"text" => "пользователь забанен ".$limitDateInfo));
    				$ws->push($frame->fd, json_encode($messages));
    			}
    		}
    	}
    }
});

$ws->on('close', function ($ws, $fd) {
	$stmt = GetStatement();
	$stmt->Execute("DELETE FROM chat_group_user WHERE FrameID=".intval($fd));
	echo "Close: {$fd}\n";
});

$ws->start();
echo "Started\n";

?>