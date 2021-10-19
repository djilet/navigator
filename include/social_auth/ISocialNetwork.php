<?php
/**
 * Date:    27.10.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

namespace SocialAuth;

interface ISocialNetwork
{
    public function __construct($name);
    public function getAuthUrl();
    public function saveToken();
    public function setToken($token);
    public function getToken();
    public function getUserInfo();
    public function getSocialType();
    public function getAppId();
    public function getBirthDay();
    public function setRedirectURL($url);
}