<?php
require_once dirname(__FILE__) . "/../include/init.php";
require_once dirname(__FILE__) . "/../include/swagger.php";

$stmt = GetStatement();
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
data_exhibition_city.GUID as campaign_code,
data_exhibition_city.CityTitle as city
FROM event_registrations
LEFT JOIN data_exhibition_city ON event_registrations.City = data_exhibition_city.CityTitle
WHERE event_registrations.CRMRegistrationId is NULL AND
event_registrations.EventID = 61 AND
data_exhibition_city.ExhibitionID = 61 AND
data_exhibition_city.GUID != '' AND
event_registrations.City = 'Екатеринбург' LIMIT 8";

$allUsers = $stmt->FetchList($query);

$familyGroups = [];
// create family group
foreach ($allUsers as $key => $user) {
    if ($user['BaseRegistrationID'] == null) {
        $familyGroup = [];
        foreach ($allUsers as $relative) {
            if ($relative['BaseRegistrationID'] == $user['RegistrationID']) {
                $familyGroup[] = $relative;
            }
        }
        $familyGroup[] = $user;
        $familyGroups[] = $familyGroup;
    }
}

function saveRegistrationId($usersId, $registrationId)
{
    $stmt = GetStatement();
    foreach ($usersId as $id) {
        $query = "UPDATE event_registrations SET CRMRegistrationId=" . Connection::GetSQLString($registrationId) .
        " WHERE RegistrationID=" . Connection::GetSQLString($id);

        $stmt->Execute($query);
    }
}

// prepare family before send and sending
$swager = new Swagger();

foreach ($familyGroups as $family) {
    $parents = [];
    $children = [];
    $extraParents = [];
    foreach ($family as $user) {
        if ($user['Who'] == 'Родитель' && (count($parents) < 2)) {
            if (count($parents) == 0) {
                $parents['first_parent'] = $user;
            } else {
                $parents['second_parent'] = $user;
            }
        } else {
            if ($user['Who'] == 'Родитель') {
                $extraParents[] = $user;
            } else {
                $children[] = $user;
            }
        }
    }
// *************************************************
    // sending for extra parents
    if (count($extraParents) > 0) {
        foreach ($extraParents as $parent) {
            $parentData = [];
            $parentData['first_parent'] = [
                'email' => $parent['email'],
                'phone' => $parent['phone'],
                'firstName' => $parent['firstName'],
                'lastname' => $parent['lastName'],
            ];

            $parentData['ut_mcontent'] = $parent['utm_content'];
            $parentData['utm_source'] = $parent['utm_source'];
            $parentData['utm_medium'] = $parent['utm_medium'];
            $parentData['utm_campaign'] = $parent['utm_campaign'];
            $parentData['ut_mterm'] = $parent['utm_campaign'];
            $parentData['city'] = $parent['city'];
            if ($parent['UserTime'] > date('Y') . '-07-31 10:00') {
                $parentData['issue_year'] = date('Y') - $parent['Class'] + 12;
            } else {
                $parentData['issue_year'] = date('Y') - $parent['Class'] + 11;
            }

            $registrationId = json_decode($swager->sendFamilyToCRMScriptVersion($parentData, [$parent['RegistrationID']], $parent['campaign_code']))->registrationId;
            if ($registrationId) {
                saveRegistrationId([$parent['RegistrationID']], $registrationId);
            }
        }
    }
// -------------------------------
    if (count($children) > 0) {
        foreach ($children as $child) {
            $group = [];
            $usersId = [];
            foreach ($parents as $key => $parent) {
                $group[$key] = $parent;
                $usersId[] = $parent['RegistrationID'];
            }
            $usersId[] = $child['RegistrationID'];
            $group['schoolchild'] = $child;
            $group['ut_mcontent'] = $child['ut_mcontent'];
            $group['utm_source'] = $child['utm_source'];
            $group['utm_medium'] = $child['utm_medium'];
            $group['utm_campaign'] = $child['utm_campaign'];
            $group['ut_mterm'] = $child['ut_mterm'];
            $group['city'] = $child['city'];

            if ($child['Who'] == 'Студент') {
                $group['issue_year'] = 2020;
            } else {
                if ($child['UserTime'] > date('Y') . '-07-31 10:00') {
                    $group['issue_year'] = date('Y') - $child['Class'] + 12;
                } else {
                    $group['issue_year'] = date('Y') - $child['Class'] + 11;
                }
            }
            $registrationId = json_decode($swager->sendFamilyToCRMScriptVersion($group, $usersId, $child['campaign_code']))->registrationId;
            if ($registrationId) {
                saveRegistrationId($usersId, $registrationId);
            }
        }
    } else {
        $group = [];
        $guid = '';
        $usersId = [];
        foreach ($parents as $key => $parent) {
            $group[$key] = $parent;
            if ($key == 'first_parent') {
                $guid = $parent['campaign_code'];
                $group['ut_mcontent'] = $parent['ut_mcontent'];
                $group['utm_source'] = $parent['utm_source'];
                $group['utm_medium'] = $parent['utm_medium'];
                $group['utm_campaign'] = $parent['utm_campaign'];
                $group['ut_mterm'] = $parent['ut_mterm'];
                $group['city'] = $parent['city'];
                if ($parent['UserTime'] > date('Y') . '-07-31 10:00') {
                    $group['issue_year'] = date('Y') - $parent['Class'] + 12;
                } else {
                    $group['issue_year'] = date('Y') - $parent['Class'] + 11;
                }
            }
            $usersId[] = $parent['RegistrationID'];
        }
        $registrationId = json_decode($swager->sendFamilyToCRMScriptVersion($group, $usersId, $guid))->registrationId;
        if ($registrationId) {
            saveRegistrationId($usersId, $registrationId);
        }
    }
}
echo 'конец';