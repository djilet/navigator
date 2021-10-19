<?php
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

$dbConfig = array(
    'host' => GetFromConfig("Host", "mysql"),
    'port' => 3306,
    'user' => GetFromConfig("User", "mysql"),
    'password' => GetFromConfig("Password", "mysql"),
    'database' => GetFromConfig("Database", "mysql"),
    'charset' => 'utf8',
    'timeout' => 2,
);

$ws->on('open', function ($ws, $request) use($dbConfig) {
    $frameID = $request->fd;
    $groupID = $request->get["GroupID"];
    $userID = $request->get["UserID"];
    if($frameID && $groupID && $userID)
    {
        $db = new swoole_mysql;
        $db->connect($dbConfig, function ($db, $r) use($ws, $frameID, $groupID, $userID) {
            $sql = "SELECT COUNT(*) as Count FROM chat_group WHERE GroupID=".intval($groupID);
            $db->query($sql, function($db, $r) use($ws, $frameID, $groupID, $userID) {
                if($r[0]["Count"] > 0) {
                    $sql = "DELETE FROM chat_group_user WHERE FrameID=".intval($frameID);
                    $db->query($sql, function($db, $r) use($ws, $frameID, $groupID, $userID) {
                        $sql = "INSERT INTO chat_group_user(FrameID, GroupID, UserID) VALUES(".intval($frameID).",".intval($groupID).",".intval($userID).")";
                        $db->query($sql, function($db, $r) use($ws, $frameID, $groupID, $userID) {
                            //send all history on connect
                            $messages = array();
                            $sql = "SELECT m.MessageID, m.UserID, m.Message, m.Created, u.UserName
    			                 FROM chat_message m
    			                 LEFT JOIN users_item u ON m.UserID=u.UserID
    			                 WHERE m.GroupID=".intval($groupID)." ORDER BY m.Created ASC";
                            $db->query($sql, function($db, $items) use($ws, $frameID) {
                                $db->close();
                                $messages = array();
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
                            });
                        });
                    });
                }
                else {
                    $db->close();
                }
            });
        });
    }
    echo "Open: {$frameID}\n";
});

$ws->on('message', function ($ws, $frame) use($dbConfig)  {
    $now = date("Y-m-d H:i:s");
    $db = new swoole_mysql;
    $db->connect($dbConfig, function ($db, $r) use($ws, $frame, $now) {
        $sql = "SELECT gu.GroupID, gu.UserID, u.UserName, u.ChatStatus, IF(u.ChatLimitDate < '".$db->escape($now)."', 1 ,0) as ChatLimitExpired
    	   FROM chat_group_user gu
    	   LEFT JOIN users_item u ON gu.UserID=u.UserID
    	   WHERE gu.FrameID=".intval($frame->fd);
        $db->query($sql, function($db, $r) use($ws, $frame, $now) {
            if(count($r) > 0) {
                $messageInfo = $r[0];
                
                $available = true;
                if($messageInfo["ChatStatus"] == 'locked'){
                    $available = false;
                    if($messageInfo["ChatLimitExpired"]) {
                        $db->query("UPDATE users_item u SET u.ChatStatus='simple',u.ChatLimitDate=NULL WHERE u.UserID=".$messageInfo["UserID"], function($db, $r) {});
                        $available = true;
                    }
                }
                
                $request = json_decode($frame->data, true);
                if($request["action"] == "add")
                {
                    if($available)
                    {
                        $text = $request["text"];
                        $sql = "INSERT INTO chat_message(GroupID, UserID, Message, Created) VALUES(".$messageInfo["GroupID"].",".$messageInfo["UserID"].",'".$db->escape($text)."','".$db->escape($now)."')";
                        $db->query($sql, function($db, $r) use($ws, $messageInfo, $text, $now) {
                            if($r){
                                $messages = array();
                                $messageID = $db->insert_id;
                                $message = array("action" => "add",
                                    "id" => $messageID,
                                    "text" => PrepareText($text),
                                    "user" => $messageInfo["UserName"],
                                    "created" => $now);
                                $messages[] = $message;
                                $sql = "SELECT FrameID FROM chat_group_user WHERE GroupID=".$messageInfo["GroupID"];
                                $db->query($sql, function($db, $items) use($ws, $messages) {
                                    $db->close();
                                    for($i=0; $i<count($items); $i++){
                                        $ws->push($items[$i]["FrameID"], json_encode($messages));
                                    }
                                });
                            }
                            else {
                                $db->close();
                            }
                        });
                    }
                    else
                    {
                        $db->close();
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
                    $sql = "DELETE FROM chat_message WHERE MessageID=".intval($messageID);
                    $db->query($sql, function($db, $r) use($ws, $messageID, $messageInfo) {
                        $messages = array();
                        $message = array("action" => "remove",
                            "id" => $messageID);
                        $messages[] = $message;
                        $sql = "SELECT FrameID FROM chat_group_user WHERE GroupID=".$messageInfo["GroupID"];
                        $db->query($sql, function($db, $items) use($ws, $messages) {
                            $db->close();
                            for($i=0; $i<count($items); $i++){
                                $ws->push($items[$i]["FrameID"], json_encode($messages));
                            }
                        });
                    });
                }
                elseif($request["action"] == "ban" && $messageInfo["ChatStatus"] == 'moderator')
                {
                    $messageID = $request["messageID"];
                    $sql = "SELECT UserID FROM chat_message WHERE MessageID=".intval($messageID);
                    $db->query($sql, function($db, $r) use($ws, $request, $messageID, $frame) {
                        if(count($r) > 0){
                            $userID = $r[0]["UserID"];
                            $limitDate = "NULL";
                            $limitDateInfo = "навсегда";
                            if($request["time"] == "day")
                            {
                                $date = strtotime("+1 day");
                                $dateStr = date("Y-m-d H:i:s", $date);
                                $limitDate = $db->escape($dateStr);
                                $limitDateInfo = "до ".$dateStr;
                            }
                            elseif($request["time"] == "week")
                            {
                                $date = strtotime("+7 day");
                                $dateStr = date("Y-m-d H:i:s", $date);
                                $limitDate = $db->escape($dateStr);
                                $limitDateInfo = "до ".$dateStr;
                            }
                            
                            $sql = "UPDATE users_item SET ChatStatus='locked',ChatLimitDate='".$limitDate."' WHERE UserID=".intval($userID);
                            $db->query($sql, function($db, $r) use($ws, $frame, $limitDateInfo) {
                                $db->close();
                                $messages = array(array("action" => "info",
                                    "text" => "пользователь забанен ".$limitDateInfo));
                                $ws->push($frame->fd, json_encode($messages));
                            });
                        }
                        else {
                            $db->close();
                        }
                    });
                }
            }
            else {
                $db->close();
            }
        });
    });
});

$ws->on('close', function ($ws, $fd) use($dbConfig) {
    $db = new swoole_mysql;
    $db->connect($dbConfig, function ($db, $r) use($fd) {
        $sql = "DELETE FROM chat_group_user WHERE FrameID=".intval($fd);
        $db->query($sql, function($db, $r) use($fd) {
            $db->close();
            echo "Close: {$fd}\n";
        });
    });
});

$ws->start();

?>