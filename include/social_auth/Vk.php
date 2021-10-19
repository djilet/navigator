<?php
/**
 * Date:    27.10.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

namespace SocialAuth;

class Vk implements ISocialNetwork
{
    private $clientID = '6144900';
    private $clientSecret = 'yf5DNtA1ApsB09lNPLG8';
    private $token = false;
    private $apiVersion = '5.131';
    private $name;

    private $email;
    private $user;
    private $birthDay;
    
    private $redirectURL;
    
    private $logFile;

    /**
     * Vk constructor.
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->redirectURL = GetUrlPrefix().'profile/auth/vk/';
        
        $this->logFile = PROJECT_DIR . 'var/log/social.log';
    }

    public function getAuthUrl()
    {
        $url = 'https://oauth.vk.com/authorize?';
        $params = [
            'client_id' => $this->clientID,
            'redirect_uri' => $this->redirectURL,
            'scope' => 'email',
            'response_type' => 'code',
            'v' => $this->apiVersion
        ];
        
        return $url.http_build_query($params);
    }

    public function saveToken()
    {
        if (isset($_GET['code'])) {
            $url = 'https://oauth.vk.com/access_token?';
            $params = [
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectURL,
                'code' => $_GET['code']
            ];
            $response = file_get_contents($url.http_build_query($params));
            $json = @json_decode($response, true);
            if (isset($json['access_token'])) {
                $this->token = [
                    'user_id' => $json['user_id'],
                    'token'   => $json['access_token'],
                    'expire'  => $json['expires_in'] + time(),
                ];
                $this->email = $json['email'];
            }
            else {
                $this->log("Error getting access token: " . $response);
            }
        }
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getUserInfo()
    {
        if (empty($this->user)) {
            $url = 'https://api.vk.com/method/users.get?';
            $params = [
                'user_ids' => $this->token['user_id'],
                'fields' => 'id,first_name,last_name,city,bdate',
                'access_token' => $this->token['token'],
                'lang' => 0,
                'v' => $this->apiVersion
            ];
            
            $response = Request::get($url.http_build_query($params));
            if (isset($response['response'][0])) {
                $response = $response['response'][0];

                if ($response['bdate']) {
                    $this->birthDay = \DateTime::createFromFormat('d.m.Y', $response['bdate']);
                }

                $this->user = [
                    'user_id' => $response['id'],
                    'first_name' => $response['first_name'],
                    'last_name' => $response['last_name'],
                    
                    'city' => $response['city']['title'],
                    'email' => $this->email
                ];
            }
            else {
                $this->log("Error getting user info: request: " . $url.http_build_query($params) . ", response: " . serialize($response));
            }
        }

        return $this->user;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getSocialType()
    {
        return $this->name;
    }

    public function getAppId()
    {
        return $this->clientID;
    }

    public function setRedirectURL($url)
    {
        $this->redirectURL = $url;
    }

    public function getBirthDay()
    {
        if ($this->birthDay instanceof \DateTime) {
            return $this->birthDay;
        }
        return false;
    }
    
    private function log($str)
    {
        $str = date('Y-m-d H:i:s').' [VK] '.$str."\n";
        file_put_contents($this->logFile, $str, FILE_APPEND);
    }
}
