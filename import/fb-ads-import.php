<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once(dirname(__FILE__)."/../include/init.php");

$app_secret = 'e7e557c58663c394f21f910885947894'; // секретный ключ приложения
$app_id = '1964031320547175'; //id приложения
$access_token = 'EAAb6Rq0wX2cBALn7dGfguo7KXPJVGGCDi0C3IDziTx4EtK2jZCzn09srxox9QKpG5qCEIgWqhfV5v0qMz2pWtgc9i0GaLn2IyzzapI73Sk5YC7o7VKfwslt1GMyTtz4m6LEUOYWLa2ZAtZCifHmN6q0HKQE2tE2AlWtVFpffQZDZD';
$id = '108993523273204'; //айди рекламного акка (act_<AD_ACCOUNT_ID>)
$v = "v5.0"; //версия API

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

function getVkAdsCampaignIdByCampaignId($campaign_id)
{
    $stmt = GetStatement();
    $query = "SELECT `id` FROM ads_campaign WHERE `campaign_id`=".$campaign_id;
    $result = $stmt->FetchField($query);
    return $result;
}

function queryBuilder($query, $value)
{
    if ($value['spend'])
    {
        $query = $query.", spent=".$value['spend'];
    }
    if ($value['impressions'])
    {
        $query = $query.", impressions=".$value['impressions'];
    }
    if ($value['reach'])
    {
        $query = $query.", reach=".$value['reach'];
    }
    if ($value['clicks'])
    {
        $query = $query.", clicks=".$value['clicks'];
    }

    return $query;
}

function getVkAdsAdvertIdByAdId($ad_id)
{
    $stmt = GetStatement();
    $query = "SELECT `id` FROM ads_advert WHERE `advert_id`=".$ad_id;
    $result = $stmt->FetchField($query);
    return $result;
}

//получение кампаний !!!РАБОЧЕЕ!!!
$params = [
    'limit' => 1000,
    'effective_status' => ["ACTIVE", "PAUSED"],
    'fields' => "id,name,objective",
    'access_token' => $access_token,
];

$url ='https://graph.facebook.com/'.$v.'/act_'.$id.'/campaigns?'.http_build_query($params);
$campaigns = curlExec($url);

//добавление кампаний в базу
$campaign_ids = array();
foreach ($ExhibitionIDs = getExhibitionIDs() as $id => $exhibition)
{
    foreach ($campaigns['data'] as $key => $value)
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
                type='".$value['objective']."',
                source='fb'";
                $stmt->Execute($query);
            }
        }
    }
}

//проходимся по всем кампаниям !!!РАБОЧЕЕ!!!
foreach ($campaign_ids as $campaign_id)
{
    //получение статистики кампаний
    $params = [
        'limit' => 10000,
        'time_increment' => 1,
        'end_time' => strtotime('now'),
        'fields' => 'impressions,clicks,spend,reach',
        'access_token' => $access_token,
    ];
    $url = 'https://graph.facebook.com/'.$v.'/'.$campaign_id.'/insights?'.http_build_query($params);
    $data = curlExec($url);

    //добавлений сатистики в базу
    foreach ($data['data'] as $key => $value)
    {
        $founded = $stmt->FetchField("SELECT count(*) FROM ads_stats WHERE day='".$value['date_start']."' AND ads_campaign_id = ".getVkAdsCampaignIdByCampaignId($campaign_id));
        if ($founded <= 0)
        {
            $query = "INSERT INTO `ads_stats` SET
                    ads_campaign_id=".getVkAdsCampaignIdByCampaignId($campaign_id).",
                    day='".$value['date_start']."'";
        } else {
            $query = "UPDATE `ads_stats` SET
                    ads_campaign_id=".getVkAdsCampaignIdByCampaignId($campaign_id).",
                    day='".$value['date_start']."'";
        }
        $query = queryBuilder($query, $value);
        if ($founded > 0)
        {
            $query = $query." WHERE day='".$value['date_start']."' AND ads_campaign_id = ".getVkAdsCampaignIdByCampaignId($campaign_id);
        }
        $stmt->Execute($query);
    }

    //получение объявлений кампании
    $ads_ids = array();
    $params = [
        'limit' => 10000,
        'fields' => 'id,campaign_id,creative{asset_feed_spec{link_urls{website_url}}}',
        'access_token' => $access_token,
    ];
    $url = 'https://graph.facebook.com/'.$v.'/'.$campaign_id.'/ads?'.http_build_query($params);
    $data = curlExec($url);
    //print_r($data); exit();

    //добавление объявлений в базу
    foreach ($data['data'] as $key => $value)
    {
        $website_url = $value['creative']['asset_feed_spec']['link_urls'][0]['website_url'];
        if ($website_url)
        {
            $link = parse_url($website_url);
            parse_str($link['query'], $utm);
            $city = explode("/", $link['path']);
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
            $query = "INSERT INTO `ads_fb_utm` SET
                        ads_campaign_id='".$value['campaign_id']."',
                        advert_id='".$value['id']."',
                        utm_source='".$utm['utm_source']."',
                        utm_medium='".$utm['utm_medium']."',
                        utm_campaign='".$utm['utm_campaign']."',
                        utm_term='".$utm['utm_term']."',
                        utm_content='".$utm['utm_content']."'";
            $stmt->Execute($query);
        }
        $city = ''; $utm = '';
        $ads_ids[] = $value['id'];
    }

    //получение статистики объявлений
    $params = [
        'limit' => 1000000,
        'level' => 'ad',
        'time_increment' => 1,
        'end_time' => strtotime('now'),
        'fields' => 'ad_id,impressions,clicks,spend,reach',
        'access_token' => $access_token,
    ];
    $url = 'https://graph.facebook.com/'.$v.'/'.$campaign_id.'/insights?'.http_build_query($params);
    $data = curlExec($url);

    foreach ($data['data'] as $key => $value)
    {
        $founded = $stmt->FetchField("SELECT count(*) FROM ads_advert_stats WHERE day='".$value['date_start']."' AND ads_advert_id = ".getVkAdsAdvertIdByAdId($value['ad_id']));
        if ($founded <= 0)
        {
            $query = "INSERT INTO `ads_advert_stats` SET
                    ads_advert_id='".getVkAdsAdvertIdByAdId($value['ad_id'])."',
                    day='".$value['date_start']."'";
        } else {
            $query = "UPDATE `ads_advert_stats` SET
                    ads_advert_id='".getVkAdsAdvertIdByAdId($value['ad_id'])."',
                    day='".$value['date_start']."'";
        }
        $query = queryBuilder($query, $value);
        if ($founded > 0)
        {
            $query = $query." WHERE day='".$value['date_start']."' AND ads_advert_id = ".getVkAdsAdvertIdByAdId($value['ad_id']);
        }
        $stmt->Execute($query);
    }
}
print_r($data); exit();