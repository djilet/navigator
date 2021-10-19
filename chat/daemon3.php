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
        'worker_num' => GetFromConfig('ChatWorkerNum', 'chat'),
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
        $db = new Swoole\Coroutine\MySQL();
        $db->connect($dbConfig);

        $sql = "SELECT COUNT(*) as Count FROM chat_group WHERE GroupID=".intval($groupID);
        $r = $db->query($sql);
        if($r[0]["Count"] > 0) {
            $sql = "DELETE FROM chat_group_user WHERE FrameID=".intval($frameID);
            $db->query($sql);

            $sql = "INSERT INTO chat_group_user(FrameID, GroupID, UserID) VALUES(".intval($frameID).",".intval($groupID).",".intval($userID).")";
            $db->query($sql);

            //send all history on connect
            $messages = array();
            $sql = "SELECT m.MessageID, m.UserID, m.Message, m.Created, ch_us.UserName
    			                 FROM chat_message m
    			                 LEFT JOIN chat_user AS ch_us ON m.UserID=ch_us.ID
    			                 WHERE m.GroupID=".intval($groupID)." ORDER BY m.Created ASC";
            $items = $db->query($sql);
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
        $db->close();
    }
    echo "Open: {$frameID}\n";
});

$ws->on('message', function ($ws, $frame) use($dbConfig)  {
    $now = date("Y-m-d H:i:s");
    $db = new Swoole\Coroutine\MySQL();
    $db->connect($dbConfig);
    $sql = "SELECT gu.GroupID, gu.UserID, ch_us.UserName, ch_us.ChatStatus, IF(ch_us.ChatLimitDate < '".$db->escape($now)."', 1 ,0) as ChatLimitExpired
    	   FROM chat_group_user gu
    	   LEFT JOIN chat_user AS ch_us ON gu.UserID=ch_us.ID
    	   WHERE gu.FrameID=".intval($frame->fd);
    $r = $db->query($sql);
    if(count($r) > 0) {
        $messageInfo = $r[0];

        $available = true;
        if($messageInfo["ChatStatus"] == 'locked'){
            $available = false;
            if($messageInfo["ChatLimitExpired"]) {
                $db->query("UPDATE chat_user u SET u.ChatStatus='simple',u.ChatLimitDate=NULL WHERE u.ID=".$messageInfo["UserID"], function($db, $r) {});
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
                $r = $db->query($sql);
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
                    $items = $db->query($sql);
                    for($i=0; $i<count($items); $i++){
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
            $sql = "DELETE FROM chat_message WHERE MessageID=".intval($messageID);
            $db->query($sql);
            $messages = array();
            $message = array("action" => "remove",
                "id" => $messageID);
            $messages[] = $message;
            $sql = "SELECT FrameID FROM chat_group_user WHERE GroupID=".$messageInfo["GroupID"];
            $items = $db->query($sql);
            for($i=0; $i<count($items); $i++){
                $ws->push($items[$i]["FrameID"], json_encode($messages));
            }
        }
        elseif($request["action"] == "ban" && $messageInfo["ChatStatus"] == 'moderator')
        {
            $messageID = $request["messageID"];
            $sql = "SELECT UserID FROM chat_message WHERE MessageID=".intval($messageID);
            $r = $db->query($sql);
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

                $sql = "UPDATE chat_user SET ChatStatus='locked',ChatLimitDate='".$limitDate."' WHERE ID=".intval($userID);
                $db->query($sql);
                $messages = array(array("action" => "info",
                    "text" => "пользователь забанен ".$limitDateInfo));
                $ws->push($frame->fd, json_encode($messages));
            }
        }
        elseif ($request["action"] == 'enterRequest'){
            $sql = "INSERT INTO chat_user SET
                                UserName = '{$request['name']}',
                                ConnectionType = 'session',
                                ConnectionID = '{$request['sessionId']}'
                    ";
            $db->query($sql);

            if (!empty($db->insert_id)){
                $ws->push($frame->fd, json_encode([
                    [
                        "action" => "enterSuccess",
                        'chatUserId' => $db->insert_id,
                    ],
                ]));
            }
        }
        elseif ($request['action'] == 'renameUserRequest'){
            $messages = [];
            $errorMessages = [];
            if(!$available)
            {
                $errorMessages[] = [
                    'action' => 'info',
                    'text' => 'Вам запрещено выполнять данное действие'
                ];
            }

            if (empty($request['name'])){
                $errorMessages[] = [
                    'action' => 'info',
                    'text' => 'Введите имя'
                ];
            }

            if (mb_strlen($request['name']) > 45){
                $errorMessages[] = [
                    'action' => 'info',
                    'text' => 'Максимальная длина - 45 символов'
                ];
            }

            if (!empty($errorMessages)){
                $ws->push($frame->fd, json_encode($errorMessages));
            }
            else{
                $name = htmlspecialchars($request['name']);
                $sql = "UPDATE chat_user SET UserName = '{$name}' WHERE ID = '{$messageInfo["UserID"]}'";
                $db->query($sql);

                $sql = "SELECT FrameID FROM chat_group_user WHERE GroupID=".$messageInfo["GroupID"];
                $frames = $db->query($sql);

                $sql = "SELECT MessageID FROM chat_message
                        WHERE GroupID={$messageInfo["GroupID"]}
                        AND UserID = {$messageInfo["UserID"]}
                        ORDER BY Created DESC
                        LIMIT 30";
                $rows = $db->query($sql);

                $messageIds = [];
                foreach ($rows as $row){
                    $messageIds[] = $row['MessageID'];
                }

                $messages[] = [
                    'action' => 'renameUser',
                    'messageIds' => $messageIds,
                    'name' => $name,
                ];

                $jsonMessages = json_encode($messages);
                for($i=0; $i<count($frames); $i++){
                    $ws->push($frames[$i]["FrameID"], $jsonMessages);
                }
            }
        } elseif ($request['action'] == 'getLiveCount') {
            // $sql = "SELECT FrameID FROM chat_group_user WHERE GroupID=".$messageInfo["GroupID"];
            // $frames = $db->query($sql);
            // $liveFramesCount = 0;

            // for($i=0; $i<count($frames); $i++) {
            //     if ($ws->getClientInfo($frames[$i]["FrameID"])) {
            //         $liveFramesCount++;
            //     }
            // }

            $messages = array(array(
                'action' => 'liveCountInfo',
                'liveCount' => 0,
            ));
            $ws->push($frame->fd, json_encode($messages));
        }
    }
});

$ws->on('close', function ($ws, $fd) use($dbConfig) {

    $db = new Swoole\Coroutine\MySQL();
    $db->connect($dbConfig);
    $sql = "DELETE FROM chat_group_user WHERE FrameID=".intval($fd);
    $db->query($sql);

    echo "Close: {$fd}\n";
    $db->close();
});

$ws->start();

?>