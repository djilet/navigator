<?php
require_once(dirname(__FILE__)."/session.php");

class RedisSession extends Session
{
    private $redis;
    public function __construct($sessionName)
    {
        $this->redis = new Redis();
        $this->redis->connect(GetFromConfig('Host', 'redis'));
        $this->redis->select(GetFromConfig('Database', 'redis'));
        parent::Session($sessionName);
    }

    function SaveToDB($inCookie = null)
    {
        if (count($this->GetProperties()) > 0)
        {
            if (!is_null($inCookie))
            {
                // We have to overwrite current InCookie field
                if ($inCookie)
                    $this->_inCookie = true;
                else
                    $this->_inCookie = false;
            }

            $sessionInterval = 60*60*24; // Keep session in database only for 24 hours
            $inCookie = 0;
            if ($this->_inCookie)
            {
                $sessionInterval = 60*60*24*30*COOKIE_EXPIRE;
                $inCookie = 1;
            }

            // Field UserID is needed to determine which sessions to remove when we remove user
            $userID = null;
            $loggedInUser = $this->GetProperty("LoggedInUser");
            if (isset($loggedInUser["UserID"]))
                $userID = $loggedInUser["UserID"];

            $language =& GetLanguage();
            $encoding = $language->GetMySQLEncoding();
            // Switch to utf8 to save serialized array correctly
            if ($encoding != "utf8")
            {
                //$stmt->Execute("SET NAMES utf8");
            }

            $a = $this->GetProperties();
            // Convert website encoding to utf8 before save
            if ($encoding != "utf8")
            {
                $this->_Convert($a, "UTF-8", $language->GetHTMLCharset());
            }

            // We have to synchronoze database & cookie session interval
            if ($this->UpdateCookie($sessionInterval))
            {
                $this->_sessionExist = true;
            }

            $this->redis->hmset($this->_sessionID, [
                'InCookie' => $inCookie,
                'SessionData' => serialize($a),
                'UserID' => $userID,
            ]);
            $this->redis->expire($this->_sessionID, $sessionInterval);

            // Switch to default website encoding
            if ($encoding != "utf8")
            {
                //$stmt->Execute("SET NAMES ".$encoding);
            }
        }
        else
        {
            // No need to store empty session
            $this->redis->delete($this->_sessionID);
        }
    }

    function LoadFromDB()
    {
        $result = false;
        $language =& GetLanguage();
        $encoding = $language->GetMySQLEncoding();
        // Switch to utf8 to load serialized array correctly
        if ($encoding != "utf8")
        {
            //$stmt->Execute("SET NAMES utf8");
        }

        $data = $this->redis->hgetall($this->_sessionID);

        if ($data)
        {
            if ($data["InCookie"] > 0)
                $this->_inCookie = true;
            else
                $this->_inCookie = false;

            if ($a = unserialize($data["SessionData"]))
            {
                // Convert utf8 to website encoding
                if ($encoding != "utf8")
                {
                    $this->_Convert($a, $language->GetHTMLCharset(), "UTF-8");
                }
                //print_r($a);
                $this->LoadFromArray($a);
            }

            $result = true;
        }

        // Switch to default website encoding
        if ($encoding != "utf8")
        {
            //$stmt->Execute("SET NAMES ".$encoding);
        }

        return $result;
    }

    function UpdateExpireDate()
    {
        if ($this->SessionExist())
        {
            //$stmt = GetStatement();

            $sessionInterval = 60*60*24; // Keep session in database only for 24 hours
            if ($this->_inCookie)
                $sessionInterval = 60*60*24*30*COOKIE_EXPIRE;

            // We have to synchronoze database & cookie session interval
            $this->UpdateCookie($sessionInterval);

            $this->redis->expire($this->_sessionID, $sessionInterval);
        }
    }

    function RemoveExpired()
    {
        return true;
    }
}