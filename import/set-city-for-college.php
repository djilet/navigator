<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../module/college/init.php");

$collegeList = GetStatement()->FetchList("SELECT CollegeID, Title, Latitude, Longitude, Address FROM college_college WHERE CityID IS NULL");
$cityList = GetStatement()->FetchIndexedAssocList("SELECT * FROM data_city", 'Title');

foreach ($collegeList as $key => $item){
    if (empty($item['Longitude']) || empty($item['Latitude'])){
        echo "without coords [{$item['CollegeID']}] \n";
        continue;
    }
    $params = [
        'apikey' => GetFromConfig('GeoCodeApiKey', 'yandex'),
        'geocode' => "{$item['Longitude']}, {$item['Latitude']}",
        'kind' => 'locality',
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

    //get city name
    $cityName = $response->response->GeoObjectCollection->featureMember[0]->GeoObject->name;
    if (empty($cityName)){
        echo "empty city name [{$item['CollegeID']}] \n";
        continue;
    }

    if (isset($cityList[$cityName])){
        $city = $cityList[$cityName];
        GetStatement()->Execute("UPDATE `college_college` SET `CityID` = {$city['ID']} WHERE `CollegeID` = {$item['CollegeID']};");
        echo "true [{$item['CollegeID']}] \n";
    }
    else{
        echo "not city [{$cityName}] in db [{$item['CollegeID']}] \n";
    }
}

fclose($file);