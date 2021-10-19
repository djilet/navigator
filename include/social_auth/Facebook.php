<?php
/**
 * Date:    30.10.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

namespace SocialAuth;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class Facebook implements ISocialNetwork
{
    private $appID = '1964031320547175';
    private $appSecret = 'e7e557c58663c394f21f910885947894';
    private $version = 'v2.10';

    private $user;

    /** @var  \Facebook\Authentication\AccessToken */
    private $token;

    /** @var  \Facebook\Facebook */
    private $fb;

    private $name;

    private $birthDay;

    private $logFile;
    
    private $redirectURL;

    public function __construct($name)
    {
        $this->name = $name;
        $this->redirectURL = GetUrlPrefix() . 'profile/auth/fb/';

        $this->fb = new \Facebook\Facebook([
            'app_id'                => $this->appID,
            'app_secret'            => $this->appSecret,
            'default_graph_version' => $this->version,
        ]);

        $this->logFile = PROJECT_DIR . 'var/log/social.log';
    }

    public function getAuthUrl()
    {
        $helper = $this->fb->getRedirectLoginHelper();
        $permission = ["email", "user_location", "user_birthday"];

        return $helper->getLoginUrl($this->redirectURL, $permission);
    }

    public function saveToken()
    {
    	$helper = $this->fb->getRedirectLoginHelper();

        if (isset($_GET['state'])) {
            $helper->getPersistentDataHandler()->set('state', $_GET['state']);
        }

        try {
            $accessToken = $helper->getAccessToken($this->redirectURL);
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            $this->log('Graph returned an error: ' . $e->getMessage());
            return;
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            $this->log('SDK returned an error: ' . $e->getMessage());
            return;
        }

        if (!isset($accessToken)) {
            return;
        }

        $oAuth2Client = $this->fb->getOAuth2Client();
        $tokenMeta = $oAuth2Client->debugToken($accessToken);
        $tokenMeta->validateAppId($this->appID);
        $tokenMeta->validateExpiration();

        if (! $accessToken->isLongLived()) {
            try {
                $longAccessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                $accessToken = $longAccessToken;
            } catch (FacebookSDKException $e) {
                $this->log("Error getting long-lived access token: " . $helper->getMessage());
            }
        }

        $this->setToken($accessToken);
    }

    public function setToken($token)
    {
        $this->token = $token;
        $this->fb->setDefaultAccessToken($token);
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getUserInfo()
    {
        if (empty($this->user)) {
            try {
                // Get the \Facebook\GraphNodes\GraphUser object for the current user.
                // If you provided a 'default_access_token', the '{access-token}' is optional.
                $response = $this->fb->get('/me?fields=id,first_name,last_name,email,birthday,location&locale=ru_RU');
            } catch (FacebookResponseException $e) {
                // When Graph returns an error
                $this->log('/me: Graph returned an error: ' . $e->getMessage());
                return false;
            } catch (FacebookSDKException $e) {
                // When validation fails or other local issues
                $this->log('/me: SDK returned an error: ' . $e->getMessage());
                return false;
            }

            $me = $response->getGraphUser();

            $location = ($loc = $me->getLocation()) ? $loc->getLocation() : null;
            $this->user = [
                'user_id' => $me->getId(),
                'first_name' => $me->getFirstName(),
                'last_name' => $me->getLastName(),
                'city' => (!empty($location) ? $location->getCity() : ''),
                'email' => $me->getEmail()
            ];

            $this->birthDay = $me->getBirthday();

        }

        return $this->user;
    }

    public function getSocialType()
    {
        return $this->name;
    }

    private function log($str)
    {
        $str = date('Y-m-d H:i:s').' [FACEBOOK] '.$str."\n";
        file_put_contents($this->logFile, $str, FILE_APPEND);
    }
    
    public function getAppId()
    {
        return $this->appID;
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


}
