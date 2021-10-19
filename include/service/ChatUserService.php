<?php


class ChatUserService
{
    protected $logger;
    public function __construct()
    {
        $this->logger = new \Logger(PROJECT_DIR . 'var/log/chat.log');
    }

    public function updateOrCreateByConnection(
        $connectionType,
        $connectionId,
        string $userName,
        string $chatStatus = null
    )
    {
        $chatUser = null;
        if ($connectionType == ChatUser::CONNECTION_TYPE_SESSION){
            $chatUser = ChatUser::getBySessionID($connectionId);
        }
        elseif ($connectionType == ChatUser::CONNECTION_TYPE_USER){
            $chatUser = ChatUser::getByUserID($connectionId);
        }

        if (!$chatUser){
            $chatUser = new ChatUser();
        }

        $chatUser->UserName = $userName;
        $chatUser->ConnectionType = $connectionType;
        $chatUser->ConnectionID = $connectionId;
        $chatUser->ChatStatus = $chatStatus;
        if (!$chatUser->save()){
            $this->logger->error("Create chat user - Type:{$connectionType}, Id: {$connectionId}");
            $chatUser = null;
        }

        return $chatUser;
    }
}