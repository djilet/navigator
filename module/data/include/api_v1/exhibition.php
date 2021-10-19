<?php

class DataExhibition extends LocalObject
{
    public function getList(LocalObject $request)
    {
        $stmt = GetStatement();

        $userID = $stmt->FetchField('SELECT ItemID FROM `user_item2device` WHERE Device='.$request->GetPropertyForSQL('AuthDeviceID'));
        if (!$userID) {
            // Хм, как-то получилось, что девайс уже не авторизован.
            return array();
        }

        $query = 'SELECT e.*, IF(e2u.RegistrationID IS NOT NULL, 1, 0) AS isRegister, e2u.City as SelectedCity
            FROM `data_exhibition` AS e
            LEFT JOIN `event_registrations` AS e2u ON e.ExhibitionID=e2u.EventID AND e2u.UserID='.$userID.'
            WHERE e.`DateTo` > NOW()';

        $exhibitions = $stmt->FetchList($query);
        if ($exhibitions) {

            $fmt = new IntlDateFormatter(
                'ru_RU',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                'Europe/Moscow',
                IntlDateFormatter::GREGORIAN
            );
            $fmt->setPattern('d MMMM YYYY HH:mm');

            foreach ($exhibitions as $key => $exhibition) {

                $query = 'SELECT * FROM `data_exhibition_city` WHERE `Active`="Y" AND `ExhibitionID`='.$exhibition['ExhibitionID'];
                if ($cityList = $stmt->FetchList($query)) {

                    $cities = array();
                    foreach ($cityList as &$city) {
                        $city['InfoList'] = json_decode($city['InfoList']);
                        $city['Date'] = $fmt->format(new DateTime($city['Date']));
                        $city['Selected'] = $city['CityTitle'] == $exhibitions[$key]['SelectedCity'];
                        $cities[] = $city['CityTitle'];
                    }

                    $exhibitions[$key]['ImageUrl'] = GetUrlPrefix().'website/'.WEBSITE_FOLDER.'/template/img/webinar-app.jpg';
                    $exhibitions[$key]['Period'] = $this->humanDateRanges($exhibition['DateFrom'], $exhibition['DateTo']);
                    $exhibitions[$key]['isRegister'] = ($exhibitions[$key]['isRegister'] == 1);
                    $exhibitions[$key]['CityList'] = $cityList;
                    $exhibitions[$key]['Cities'] = implode(', ', $cities);
                }

            }
        }

        return $exhibitions;
    }


    function humanDateRanges($start, $end) {

        $fmt = new IntlDateFormatter(
            'ru_RU',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Europe/Moscow',
            IntlDateFormatter::GREGORIAN
        );

        $startTime = new DateTime($start);
        $endTime = new DateTime($end);

        if ($startTime == $endTime) {
            $fmt->setPattern('d MMMM');
            return $fmt->format($startTime);
        }

        if ($startTime->format('m') != $endTime->format('m')) {
            $fmt->setPattern('d MMMM');
            return $fmt->format($startTime).' - '.$fmt->format($endTime);
        }

        $fmt->setPattern('d MMMM');
        return $startTime->format('d').' - '.$fmt->format($endTime);
    }

    public function barcode($userID, $exhibitionID)
    {
        $stmt = GetStatement();
        $phone = $stmt->FetchField('SELECT `Phone` FROM `event_registrations`
            WHERE `EventID` ='.intval($exhibitionID).' AND `UserID`='.intval($userID));

        $d = new \Milon\Barcode\DNS1D();
        $d->setStorPath(PROJECT_DIR."/var/image/");

        header('Content-type: image/png');
        echo base64_decode($d->getBarcodePNG($phone, "C39", 3, 120));
    }

}