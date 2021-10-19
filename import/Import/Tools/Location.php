<?php

namespace Import\Tools;

class Location
{
    public static function getCoordinateByAddress($address)
    {
        $point = new CoorPoint();
        
        if (!empty($address)) {
            $params = [
                'apikey' => GetFromConfig('GeoCodeApiKey', 'yandex'),
                'geocode' => $address,
                'format'  => 'json',
                'results' => 1,
            ];

            $url = 'http://geocode-maps.yandex.ru/1.x/?' . http_build_query($params, '', '&');
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$response = json_decode(curl_exec($ch));

            if ($response) {
                $result = $response->response->GeoObjectCollection->featureMember[0]->GeoObject->Point->pos;
                if ($result != null) {
                    $result = explode(" ", $result);
                    $point->latitude = $result[1];
                    $point->longitude = $result[0];
                }
            }
        }
        
        return $point;
    }
}
