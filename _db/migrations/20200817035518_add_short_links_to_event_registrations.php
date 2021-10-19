<?php

use Phinx\Migration\AbstractMigration;

class AddShortLinksToEventRegistrations extends AbstractMigration
{
    public function up()
    {

        $sql = "SELECT RegistrationID FROM event_registrations WHERE EventID=34";
        $registrationIDs = $this->fetchAll($sql);

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
                    'X-AUTH-TOKEN: jvuhNJgV59Wo6HRmtVxEgDEU',
                    'Content-Type: application/json'
                ]);
                curl_setopt($curlObj, CURLOPT_POST, 1);
                curl_setopt($curlObj, CURLOPT_POSTFIELDS, json_encode($data));

                $response = curl_exec($curlObj);
                curl_close($curlObj);

                if ($response)
                {
                    $result = json_decode($response);
                    $shortLink = $result->data->attributes->full_url;

                    if($shortLink)
                    {
                        $sql = "UPDATE event_registrations SET ShortLink='" . $shortLink . "' WHERE RegistrationID=" . $registrationID['RegistrationID'];
                        $this->query($sql);
                    }
                }
            }
        }
    }

    public function down()
    {
        $sql = "UPDATE event_registrations SET ShortLink=NULL WHERE EventID=34";
        $this->query($sql);
    }
}
