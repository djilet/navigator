<?php
require_once dirname(__FILE__) . "/../include/init.php";
require_once dirname(__FILE__) . "/../include/swagger.php";

$request = new LocalObject($_POST, $_GET);
$ids = $_GET['id'];

$stmt = GetStatement();

$swager = new Swagger();

function saveRegistrationId($usersId, $registrationId)
{
    $stmt = GetStatement();
    foreach ($usersId as $id) {
        $query = "UPDATE event_registrations SET CRMRegistrationId=" . Connection::GetSQLString($registrationId) .
        " WHERE RegistrationID=" . Connection::GetSQLString($id);

        $stmt->Execute($query);
    }
}

foreach ($ids as $id) {
    $arrayToSend = [];

    $query = "SELECT
    event_registrations.RegistrationID,
    event_registrations.BaseRegistrationID,
    event_registrations.Who,
    event_registrations.Email as email,
    event_registrations.Phone as phone,
    event_registrations.FirstName as firstName,
    event_registrations.LastName as lastName,
    event_registrations.Class,
    event_registrations.utm_content as ut_mcontent,
    event_registrations.utm_term as ut_mterm,
    event_registrations.utm_source as utm_source,
    event_registrations.utm_medium as utm_medium,
    event_registrations.utm_campaign as utm_campaign,
    event_registrations.Created as UserTime,
    data_exhibition_city.GUID as campaign_code
    FROM event_registrations
    LEFT JOIN data_exhibition_city ON event_registrations.City = data_exhibition_city.CityTitle
    WHERE event_registrations.RegistrationID = $id AND
    data_exhibition_city.GUID != '' AND
    data_exhibition_city.ExhibitionID = 61";

    $user = $stmt->FetchList($query)[0];

    if ($user['Who'] == 'Родитель') {
        $arrayToSend['first_parent'] = $user;
    } else {
        $arrayToSend['schoolchild'] = $user;
    }

    $arrayToSend['ut_mcontent'] = $user['ut_mcontent'];
    $arrayToSend['utm_source'] = $user['utm_source'];
    $arrayToSend['utm_medium'] = $user['utm_medium'];
    $arrayToSend['utm_campaign'] = $user['utm_campaign'];
    $arrayToSend['ut_mterm'] = $user['ut_mterm'];
    if ($user['UserTime'] > date('Y') . '07-31 10:00') {
        $arrayToSend['issue_year'] = date('Y') - $user['Class'] + 12;
    } else {
        $arrayToSend['issue_year'] = date('Y') - $user['Class'] + 11;
    }

    $registrationId = json_decode($swager->sendFamilyToCRMScriptVersion($arrayToSend, [$user['RegistrationID']], $user['campaign_code']))->registrationId;
    if ($registrationId) {
        saveRegistrationId([$user['RegistrationID']], $registrationId);
    }
}
