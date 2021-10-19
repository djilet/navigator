<?php
/**
 * Date:    27.10.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

namespace SocialAuth;

class Request
{
    public static function get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}