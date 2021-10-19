<?php
require_once(dirname(__FILE__) . "/../include/init.php");

$stmt = GetStatement();

$sql = "SELECT RegistrationID FROM event_registrations WHERE EventID=34 AND ShortLink IS NULL";
$registrationIDs = $stmt->FetchList($sql);

$resultCount = 0;

if(count($registrationIDs) > 0)
{
    foreach($registrationIDs as $registrationID)
    {
        $longLink = "https://propostuplenie.ru/exhibition?Registration=".$registrationID['RegistrationID'];

        $data = [
            'data'=> [
                'type' => 'link',
                'attributes' => [
                    'web_url' => $longLink,
                    'domain_id' => 30,
                ]
            ]
        ];

        $curlObj = curl_init();
        curl_setopt($curlObj, CURLOPT_URL, 'https://to.click/api/v1/links');
        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, [
            'X-AUTH-TOKEN: zb5D5VG45ntKbaQrFQxykUTU',
            'Content-Type: application/json'
        ]);
        curl_setopt($curlObj, CURLOPT_POST, 1);
        curl_setopt($curlObj, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($curlObj);
        curl_close($curlObj);
        $json = json_decode($response);

        if (!isset($json->error))
        {
            $sql = "UPDATE event_registrations SET ShortLink=".Connection::GetSQLString($json->data->attributes->full_url)." WHERE RegistrationID=".$registrationID['RegistrationID'];
            $stmt->Execute($sql);

            $resultCount++;
        }
        else
        {
            print_r($json->error);
            break;
        }
    }
}

echo "updated:".$resultCount;

