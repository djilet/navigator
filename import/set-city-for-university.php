<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../module/data/init.php");

$universityList = GetStatement()->FetchList("SELECT UniversityID, Title, Latitude, Longitude, Address FROM data_university WHERE CityID IS NULL");
$cityList = GetStatement()->FetchIndexedAssocList("SELECT * FROM data_city", 'Title');

$file = fopen(__DIR__ . '/source/nearby_city.csv', "r");
$nearbyCityList = [];
while (($data = fgetcsv($file, 0, ";", '"')) !== FALSE) {
    $nearbyCityList[$data[0]] = $data[1];
}
fclose($handle);


foreach ($universityList as $key => $item){
    $geoCode = "{$item['Longitude']}, {$item['Latitude']}";
    $byAddress = false;
    if (empty($item['Longitude']) || empty($item['Latitude'])){
        if (empty($item['Address'])){
            echo "without coords [{$item['UniversityID']}] \n";
            continue;
        }

        $geoCode = $item['Address'];
        $byAddress = true;
    }
    $params = [
        'apikey' => GetFromConfig('GeoCodeApiKey', 'yandex'),
        'geocode' => $geoCode,
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
    $geoObject = $response->response->GeoObjectCollection->featureMember[0]->GeoObject;
    $cityName = $geoObject->name;
    if ($byAddress){
        $cityName = null;
        $components = $geoObject
            ->metaDataProperty
            ->GeocoderMetaData
            ->Address
            ->Components;

        foreach ($components as $component){
            if ($component->kind == 'locality'){
                $cityName = $component->name;
            }
        }
    }

    if (empty($cityName)){
        echo "empty city name [{$item['UniversityID']}] \n";
        continue;
    }

    $nearbyCityName = $nearbyCityList[$cityName];

    if (isset($cityList[$cityName]) || isset($cityList[$nearbyCityName])){
        $city = isset($cityList[$cityName]) ? $cityList[$cityName] : $cityList[$nearbyCityName];
        $query = QueryBuilder::init()
            ->update('data_university')
            ->setValue('CityID', $city['ID'])
            ->addWhere("UniversityID = {$item['UniversityID']}");
        if ($byAddress){
            $coords = explode(urldecode('%20'), $geoObject->Point->pos);
            $query->setValue('Latitude', "'{$coords[1]}'");
            $query->setValue('Longitude', "'{$coords[0]}'");
        }
        GetStatement()->Execute($query->getSQL());
        echo "true [{$item['UniversityID']}] \n";
    }
    else{
        echo "not city [{$cityName}] in db [{$item['UniversityID']}] \n";
    }
}

fclose($file);