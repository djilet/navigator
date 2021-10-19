<?php
require_once(dirname(__FILE__)."/../include/init.php");

$account_id = '1900013921';
$client_id = '1605003057';
$token = '58869e480ae48186c20872d0dac628151837caf900a8138b811052e3f05b522f019d99b1f34f6ed213bdd';
$version = '5.52';

function curlExec($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Basic lock', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true
    ]);
    $result = curl_exec($ch);

    If (curl_errno($ch) == 0) {
        $data = json_decode($result, true);
    } else {
        $data = false;
    }

    curl_close($ch);

    return $data;
}

function getVkAdsCampaignIdByCampaignId($campaign_id)
{
    $stmt = GetStatement();
    $query = "SELECT `id` FROM ads_campaign WHERE `campaign_id`=".$campaign_id;
    $result = $stmt->FetchField($query);
    return $result;
}

function getVkAdsAdvertIdByAdId($ad_id)
{
    $stmt = GetStatement();
    $query = "SELECT `id` FROM ads_advert WHERE `advert_id`=".$ad_id;
    $result = $stmt->FetchField($query);
    return $result;
}

function getExhibitionIDs()
{
    $stmt = GetStatement();
    $query = "SELECT ExhibitionID FROM `data_exhibition`" ;
    $result = $stmt->FetchList($query);
    return $result;
}

function getExhibitionIdByCampaignId($campaign_id)
{
    $stmt = GetStatement();
    $query = "SELECT `exhibition_id` FROM ads_campaign WHERE `campaign_id`=".$campaign_id;
    $result = $stmt->FetchField($query);
    return $result;
}

function queryBuilder($query, $stat)
{
    if ($stat['spent'])
    {
        $query = $query.", spent=".$stat['spent'];
    }
    if ($stat['impressions'])
    {
        $query = $query.", impressions=".$stat['impressions'];
    }
    if ($stat['reach'])
    {
        $query = $query.", reach=".$stat['reach'];
    }
    if ($stat['clicks'])
    {
        $query = $query.", clicks=".$stat['clicks'];
    }

    return $query;
}

//получение кампаний
$params = [
    'account_id' => $account_id,
    'client_id' => $client_id,
    'access_token' => $token,
    'v' => $version
];

$url ='https://api.vk.com/method/ads.getCampaigns?'.http_build_query($params);
$campaigns = curlExec($url);

//получение всех id кампаний соответвующих ExhibitionID
$campaign_ids = array();
foreach ($ExhibitionIDs = getExhibitionIDs() as $id => $exhibition)
{
    foreach ($campaigns['response'] as $key => $value)
    {
        if (in_array('ExhibitionID='.$exhibition['ExhibitionID'], explode(' ', $value['name'])))
        {
            $campaign_ids[] = $value['id'];
            $founded = $stmt->FetchField("SELECT count(*) FROM ads_campaign WHERE exhibition_id=".getExhibitionIdByCampaignId($value['id']));
            if($founded <= 0)
            {
                $query = "INSERT INTO `ads_campaign` SET
                exhibition_id= ".$exhibition['ExhibitionID'].",
                campaign_id= ".$value['id'].",
                type='".$value['type']."',
                source='vk'";
                $stmt->Execute($query);
            }
        }
    }
}

if (!empty($campaign_ids))
{
    //получаем статистику по кампаниям
    $code = urlencode('return API.ads.getStatistics(
            {
                "account_id":'.$account_id.',
                "ids_type":"campaign",
                "ids": "'.implode($campaign_ids, ', ').'",
                "period":"day",
                "date_from":"0",
                "date_to":"'.date("Y-m-d").'",
            });
            ');
    $url ='https://api.vk.com/method/execute?code='.$code.'&access_token='.$token.'&v='.$version;
    $data = curlExec($url);

    //загружаем данные в базу
    foreach ($data['response'] as $key => $value){
        foreach ($value['stats'] as $stat_id => $stat)
        {
            $founded = $stmt->FetchField("SELECT count(*) FROM ads_stats WHERE day='".$stat['day']."' AND ads_campaign_id = ".getVkAdsCampaignIdByCampaignId($value['id']));
            if ($founded <= 0)
            {
                $query = "INSERT INTO `ads_stats` SET
                    ads_campaign_id=".getVkAdsCampaignIdByCampaignId($value['id']).",
                    day='".$stat['day']."'";
            } else {
                $query = "UPDATE `ads_stats` SET
                    ads_campaign_id=".getVkAdsCampaignIdByCampaignId($value['id']).",
                    day='".$stat['day']."'";
            }
            $query = queryBuilder($query, $stat);
            if ($founded > 0)
            {
                $query = $query." WHERE day='".$stat['day']."' AND ads_campaign_id = ".getVkAdsCampaignIdByCampaignId($value['id']);
            }
            $stmt->Execute($query);
        }
    }

    //получение всех объявлений кампании
    $params = [
        'account_id' => $account_id,
        'client_id' => $client_id,
        'include_deleted' => 1,
        'campaign_ids' => json_encode($campaign_ids),
        'ad_ids' => null,
        'limit' => null,
        'offset' => null,
        'access_token' => $token,
        'v' => $version
    ];

    $url ='https://api.vk.com/method/ads.getAdsLayout?'.http_build_query($params);
    $data = curlExec($url);

    //загружаем объявления в базу и формируем массив из id объявлений
    $advertIds = array();
    foreach ($data['response'] as $key => $value)
    {
        //получение города
        $url = parse_url($value['link_url']);
        parse_str($url['query'], $utm);
        if ($url['host'] != 'vk.com')
        {
            $city = explode("/", $url['path']);
        }
        //добавление в бд
        $founded = $stmt->FetchField("SELECT count(*) FROM ads_advert WHERE advert_id=".$value['id']);
        if($founded <= 0)
        {
            $query = "INSERT INTO `ads_advert` SET
                        ads_campaign_id=".getVkAdsCampaignIdByCampaignId($value['campaign_id']).",
                        advert_id=".$value['id'].",
                        city='".$city[2]."',
                        source='".$utm['utm_source']."'";
            $stmt->Execute($query);
        }
        $city = '';
        $advertIds[] = $value['id'];
    }

    if (!empty($advertIds))
    {
        //получаем статистику объявления
        $code = urlencode('return API.ads.getStatistics(
            {
                "account_id":'.$account_id.',
                "ids_type":"ad",
                "ids":"'.implode($advertIds, ',').'",
                "period":"day",
                "date_from":"0",
                "date_to":"'.date("Y-m-d").'",
            });
            ');
        $url ='https://api.vk.com/method/execute?code='.$code.'&access_token='.$token.'&v='.$version;
        $data = curlExec($url);
    }

    foreach ($data['response'] as $key => $value)
    {
        foreach ($value['stats'] as $stat_id => $stat)
        {
            $founded = $stmt->FetchField("SELECT count(*) FROM ads_advert_stats WHERE day='".$stat['day']."' AND ads_advert_id = ".getVkAdsAdvertIdByAdId($value['id']));
            if ($founded <= 0)
            {
                $query = "INSERT INTO `ads_advert_stats` SET
                    ads_advert_id=".getVkAdsAdvertIdByAdId($value['id']).",
                    day='".$stat['day']."'";
            } else {
                $query = "UPDATE `ads_advert_stats` SET
                    ads_advert_id=".getVkAdsAdvertIdByAdId($value['id']).",
                    day='".$stat['day']."'";
            }
            $query = queryBuilder($query, $stat);
            if ($founded > 0)
            {
                $query = $query." WHERE day='".$stat['day']."' AND ads_advert_id = ".getVkAdsAdvertIdByAdId($value['id']);
            }
            $stmt->Execute($query);
        }
    }
}
print_r($data);