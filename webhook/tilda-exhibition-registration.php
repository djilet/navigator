<?php
require_once(dirname(__FILE__) . "/../include/init.php");
require_once(dirname(__FILE__) . "/../include/page.php");
require_once(dirname(__FILE__) . "/../include/logger.php");
require_once(dirname(__FILE__) . "/../module/data/include/admin/exhibition_city.php");
require_once(dirname(__FILE__) . "/../module/data/include/public/PublicExhibition.php");
require_once(dirname(__FILE__) . "/../module/data/include/public/OnlineEvents.php");
require_once(dirname(__FILE__) . "/../module/users/include/user.php");

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit;
}

$params = array_merge($_GET, $_POST);
$request = new LocalObject();
foreach ($params as $key => $value) {
    $request->SetProperty($key, urldecode($value));
}

$logger = new Logger('../var/log/tilda.log');
$loggerMsg = date('Y-m-d H:i:s') . ' Exhibition registration (tranid=' . $request->GetProperty('tranid') . '): ';

$requiredFields = [
    'CityID',
    'FirstName',
    'LastName',
    'Phone',
    'Email',
    'tranid',
];

$errorFields = [];
foreach ($requiredFields as $field) {
    if (empty($request->GetProperty($field))) {
        $errorFields[$field] = $request->GetProperty($field);
    }
}

if (!in_array($request->GetProperty('Who'), (['Студент', 'Родитель', 'Ученик']))) {
    $errorFields['Who'] = $request->GetProperty('Who');
} else if ($request->GetProperty('Who') != 'Студент' && empty($request->GetProperty('Class'))) {
    $errorFields['Class'] = $request->GetProperty('Class');
} else {
    switch ($request->GetProperty('Who')) {
        case 'Студент':
            $request->SetProperty('Who', 'student');
            break;
        case 'Родитель':
            $request->SetProperty('Who', 'student');
            break;
        case 'Ученик':
            $request->SetProperty('Who', 'parent');
            break;
    }
}

if (count($errorFields) > 0) {
    $logger->info($loggerMsg . 'ERROR, incorrect fields ' . json_encode($errorFields, JSON_UNESCAPED_UNICODE));
    exit;
}

$exhibitionCity = new ExhibitionCity('data');
$exhibitionCity->loadByID($request->GetIntProperty('CityID'));
if (!$exhibitionCity->IsPropertySet('CityID')) {
    $logger->info($loggerMsg . 'ERROR, CityID ' . $request->GetIntProperty('CityID') . ' does not exist');
    exit;
}

$exhibition = new PublicExhibition('data');
$exhibition->loadByID($exhibitionCity->GetProperty('ExhibitionID'));
if (!$exhibition->IsPropertySet('ExhibitionID')) {
    $logger->info($loggerMsg . 'ERROR, ExhibitionID ' . $exhibitionCity->GetProperty('EventID') . ' does not exist');
    exit;
}

$request->SetProperty('ExhibitionID', $exhibition->GetProperty('ExhibitionID'));
$request->SetProperty('city', $exhibitionCity->GetProperty('CityTitle'));

$form = [];
$form['UserEmail'] = [$request->GetProperty('Email')];
$form['UserName'] = [$request->GetProperty('FirstName')];
$form['UserLastName'] = [$request->GetProperty('LastName')];
$form['UserWho'] = [$request->GetProperty('Who')];
$form['UserClassNumber'] = [$request->GetProperty('Class')];
$form['UserPhone'] = [$request->GetProperty('Phone')];
$form['UserInterest'] = [$request->GetProperty('Interest')];
$form['UserInterestStr'] = [$request->GetProperty('Interest')];
$form['UserTime'] = [$request->GetProperty('Time')];
$form['utm_source'] = [$request->GetProperty('utm_source')];
$form['utm_medium'] = [$request->GetProperty('utm_medium')];
$form['utm_campaign'] = [$request->GetProperty('utm_campaign')];
$form['utm_term'] = [$request->GetProperty('utm_term')];
$form['utm_content'] = [$request->GetProperty('utm_content')];

$request->SetProperty('RegisterForm', $form);

$user = new UserItem();

$result = $exhibition->registration(
    $request,
    $user,
    $exhibitionCity->GetProperties(),
    $exhibitionCity->GetProperty('StaticPath'),
    true
);

$resultMsg = $result ? 'SUCCESS' : 'ERROR ' . json_encode($params, JSON_UNESCAPED_UNICODE);
$logger->info($loggerMsg . $resultMsg);
echo "ok";
