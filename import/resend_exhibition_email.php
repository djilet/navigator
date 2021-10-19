<?php
require_once(dirname(__FILE__) . "/../include/init.php");
set_time_limit(0);

require_once (__DIR__ . '/../module/data/include/public/PublicExhibition.php');
require_once (__DIR__ . '/../module/users/include/user.php');

function send($registration, $sendEmail = false){
    if (!$city = getCityInfo($registration['EventID'], $registration['City'])){
        echo "error city for registration - {$registration['RegistrationID']}" . PHP_EOL;
    }

    //Send email
    $content = $city['EmailTemplate'];
    $content = str_replace("[FirstName]", $registration['FirstName'], $content);
    $content = str_replace("[LastName]", $registration['LastName'], $content);
    $content = str_replace("[Time]", $registration['Time'], $content);
    $content = str_replace("[Phone]", preg_replace("/[^0-9,.]/", "", $registration['Phone']), $content);
    $content = str_replace("[Address]", $city['Address'], $content);
    $language =& GetLanguage();
    $format = $language->GetDateFormat();
    $content = str_replace("[Date]", LocalDate($format, strtotime($city['Date'])), $content);
    $mapLink = "https://yandex.ru/maps/?ll=".$city['Longitude'].",".$city['Latitude']."&z=15&pt=".$city['Longitude'].",".$city['Latitude'];
    $content = str_replace("[MapLink]", $mapLink, $content);
    $content = str_replace("[TicketNumber]", $registration['RegistrationID'], $content);

    $newUser = new UserItem();
    if ($id = $newUser->getIDByEmail($registration['Email'])){
        $newUser->loadByID($id);
        $authKey = $newUser->createAuthKey();
        $content = str_replace("[AuthKey]", $authKey, $content);
    }

    if ($sendEmail === true){
        $result = SendMailFromAdmin($registration['Email'], $city['EmailTheme'], $content);
        $result = $result ? " success" : " fail";
        echo $registration['RegistrationID'] . $result . "<br />";
    }
    else{
        echo $content . '<hr>';
    }
}

function getCityInfo($exhibitionID, $cityTitle){
    static $list = [];
    $key = $exhibitionID . "-{$cityTitle}";
    $query = "SELECT * FROM data_exhibition_city WHERE ExhibitionID = {$exhibitionID} AND CityTitle = '{$cityTitle}'";
    $cityInfo = GetStatement()->FetchRow($query);
    if (!$cityInfo){
        return false;
    }
    $list[$key] = $cityInfo;

    return $list[$key];
}

// Start
$sendEmail = isset($_GET['start']);
$from = (int) $_GET['from'];
$to = (int) $_GET['to'];
$eventId = (int) $_GET['eventId'];

if (empty($from) || empty($to)){
    echo 'empty form or to';
    return false;
}

$where = "RegistrationID >= {$from} AND RegistrationID <= {$to}";
if (!empty($eventId)) {
    $where .= " AND EventID={$eventId}";
}

$registrations = GetStatement()->FetchList("SELECT * FROM event_registrations WHERE {$where}");

foreach ($registrations as $index => $registration) {
    send($registration, $sendEmail);
}
