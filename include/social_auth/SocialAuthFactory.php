<?php
/**
 * Date:    27.10.17
 *
 * @author: dolphin54rus <dolphin54rus@gmail.com>
 */

namespace SocialAuth;

class SocialAuthFactory
{
    public static function createSocial($type)
    {
        $social = false;
        switch ($type) {
            case "vk":
                $social = new Vk($type);
                break;
                
            case "fb":
                $social = new Facebook($type);
                break;
        }
        
        return $social;
    }
}
