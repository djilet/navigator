<?php

class DataVkImportList extends LocalObjectList
{
    private $module;
    public $stats;

    public function __construct($module, $data = array())
    {
        parent::LocalObjectList($data);

        $this->module = $module;
    }

    private function getVkAdsAdvertIdsByCity($city)
    {
        $stmt = GetStatement();
        $query = "SELECT id FROM ads_advert WHERE city='".$city."'";
        $result = $stmt->FetchList($query);
        $ids = array();
        foreach ($result as $key => $value)
        {
            $ids[] = $value["id"];
        }
        return $ids;
    }

    private function getCityTitleByStaticPath($path, $exhibitionId)
    {
        $stmt = GetStatement();
        $query = "SELECT `CityTitle`
            FROM `data_exhibition_city`
            WHERE ExhibitionID=".$exhibitionId."
            AND StaticPath='".$path."'";
        $result = $stmt->FetchField($query);
        return $result;
    }

    private function getUtmSourceByCampaignId($id, $campaign = true)
    {
        $stmt = GetStatement();
        if ($campaign)
        {
            $query = "SELECT source FROM ads_campaign WHERE campaign_id='".$id."'";
        } else
        {
            $query = "SELECT source FROM ads_advert WHERE advert_id='".$id."'";
        }
        $result = $stmt->FetchField($query);
        return $result;
    }

    private function getRegCount($id, $eventID, $filter)
    {
        $stmt = GetStatement();
        //определение source
        if ($filter['Ads'] == "Y")
        {
            $source = $this->getUtmSourceByCampaignId($id, false);
        } else {
            $source = $this->getUtmSourceByCampaignId($id);
        }
        //получение регистраций
        $query = "SELECT COUNT(r.RegistrationID) as RegCount, COUNT(v.VisitID) as VisitCount
                FROM `event_registrations` AS r
                LEFT JOIN `data_exhibition_visits` v ON r.RegistrationID=v.RegistrationID
                WHERE r.EventID=".$eventID;

        if ($filter['Ads'] == "Y")
        {
            $query = $query." AND r.utm_content=".$id;
        } else {
            $query = $query." AND r.utm_campaign=".$id;
        }

        //подсчет регистраций в фб по utm меткам
        if ($source == 'fb')
        {
            $query = "SELECT r.RegistrationID, r.utm_source, r.utm_medium, r.utm_campaign, r.utm_term, r.utm_content
                        FROM `event_registrations` AS r
                        WHERE EventID=".$eventID." AND utm_source ='".$source."'";
            $registrations = $stmt->FetchList($query);
            if ($filter['Ads'] == "Y")
            {
                $query = "SELECT utm_source, utm_medium, utm_campaign, utm_term, utm_content
                        FROM `ads_fb_utm`
                        WHERE advert_id ='".$id."'
                        AND utm_source != ''";
            } else {
                $query = "SELECT utm_source, utm_medium, utm_campaign, utm_term, utm_content
                        FROM `ads_fb_utm`
                        WHERE ads_campaign_id ='".$id."'
                        AND utm_source != ''";
            }
            $utms = $stmt->FetchList($query);
            foreach ($registrations as $registration)
            {
                $searching_array = [
                    'utm_source' => $registration['utm_source'],
                    'utm_medium' => $registration['utm_medium'],
                    'utm_campaign' => $registration['utm_campaign'],
                    'utm_term' => $registration['utm_term'],
                    'utm_content' => $registration['utm_content'],
                ];
                if (in_array($searching_array, $utms))
                {
                    if ($filter['Ads'] == "Y")
                    {
                        $founded = $stmt->FetchField("SELECT COUNT(RegistrationID) FROM `event_registrations` WHERE AdvertId='".$id."' AND RegistrationID=".$registration['RegistrationID']);
                        if ($founded <= 0)
                        {
                            $query = "UPDATE `event_registrations` SET AdvertId='".$id."' WHERE RegistrationID=".$registration['RegistrationID'];
                        }
                    } else {
                        $founded = $stmt->FetchField("SELECT COUNT(RegistrationID) FROM `event_registrations` WHERE CampaignId='".$id."' AND RegistrationID=".$registration['RegistrationID']);
                        if ($founded <= 0)
                        {
                            $query = "UPDATE `event_registrations` SET CampaignId='".$id."' WHERE RegistrationID=".$registration['RegistrationID'];
                        }
                    }
                    $stmt->Execute($query);
                }
            }
            $query = "SELECT COUNT(r.RegistrationID) as RegCount, COUNT(v.VisitID) as VisitCount
                FROM `event_registrations` AS r
                LEFT JOIN `data_exhibition_visits` v ON r.RegistrationID=v.RegistrationID
                WHERE r.EventID=".$eventID;

            if ($filter['Ads'] == "Y")
            {
                $query = $query." AND r.AdvertId=".$id;
            } else {
                $query = $query." AND r.CampaignId=".$id;
            }
        }

        if ($filter["Source"])
        {
            if ($filter['Ads'] != "Y")
            {
                $query = "SELECT COUNT(r.RegistrationID) as RegCount, COUNT(v.VisitID) as VisitCount
                FROM `event_registrations` AS r
                LEFT JOIN `data_exhibition_visits` v ON r.RegistrationID=v.RegistrationID
                WHERE r.EventID=".$eventID." AND utm_source='".$this->getUtmSourceByCampaignId($id)."' AND `utm_campaign` IS NOT NULL AND `utm_content` IS NOT NULL";
            }
        }

        if ($filter["Family"] == "Y")
        {
            $query = $query." AND r.BaseRegistrationID IS NULL";
        }

        if ($filter['VKReportCity'])
        {
            $query = $query." AND r.City = '".$this->getCityTitleByStaticPath($filter['VKReportCity'], $eventID)."'";
        }

        if ($filter["VKReportClass"])
        {
            $query = $query." AND Class='".$filter["VKReportClass"]."'";
        }

        if ($filter["VKReportSource"])
        {
            $query = $query." AND utm_source='".$filter["VKReportSource"]."'";
        }

        if ($filter["VKExcludeClass"])
        {
            $query = $query." AND Class NOT IN (".implode($filter["VKExcludeClass"], ',').")";
        }

        if ($filter["VkReportDateFrom"] && !$filter["VkReportDateTo"])
        {
            $query = $query." AND Created>='".$filter["VkReportDateFrom"]." 00:00:00'";
        }
        if (!$filter["VkReportDateFrom"] && $filter["VkReportDateTo"])
        {
            $query = $query." AND Created<='".$filter["VkReportDateTo"]." 23:59:59'";
        }
        if ($filter["VkReportDateFrom"] && $filter["VkReportDateTo"])
        {
            $query = $query." AND Created>='".$filter["VkReportDateFrom"]." 00:00:00'
                              AND Created<='".$filter["VkReportDateTo"]." 23:59:59'";
        }

        if ($filter["Source"])
        {
            if ($filter['Ads'] == "Y")
            {
                $query = $query." AND utm_source='".$this->getUtmSourceByCampaignId($id, false)."'";
            }
        }

        $result = $stmt->FetchList($query);

        return $result[0];
    }

    public function load($onPage = 40, $fullList = false, $eventID = false, $request)
    {
        $stmt = GetStatement();
        $filter = $request->GetProperty("VkReportFilter");

        //рабочая версия
        if ($filter['Ads'] == "Y")
        {
            $query = "SELECT SUM(vaas.spent) AS spent, SUM(vaas.impressions) AS impressions, SUM(vaas.clicks) AS clicks, SUM(vaas.reach) AS reach, vac.campaign_id, vaa.advert_id, vaa.source
                    FROM ads_advert_stats as vaas
                    INNER JOIN ads_advert as vaa ON vaas.ads_advert_id = vaa.id
                    INNER JOIN ads_campaign as vac ON vaa.ads_campaign_id = vac.id
                    WHERE vac.exhibition_id=".$eventID;
        } else {
            $query = "SELECT SUM(vas.spent) AS spent, SUM(vas.impressions) AS impressions, SUM(vas.clicks) AS clicks, SUM(vas.reach) AS reach, vac.campaign_id, vac.source
                    FROM ads_stats as vas
                    INNER JOIN ads_campaign as vac
                    ON vas.ads_campaign_id = vac.id
                    WHERE vac.exhibition_id=".$eventID;
        }

        if ($filter["VKReportCity"] != "0")
        {
            $query = "SELECT SUM(vaas.spent) AS spent, SUM(vaas.impressions) AS impressions, SUM(vaas.clicks) AS clicks, SUM(vaas.reach) AS reach, vac.campaign_id, vaa.advert_id, vaa.source
                    FROM ads_advert_stats as vaas
                    INNER JOIN ads_advert as vaa ON vaas.ads_advert_id = vaa.id
                    INNER JOIN ads_campaign as vac ON vaa.ads_campaign_id = vac.id
                    WHERE vac.exhibition_id=".$eventID."
                    AND vaas.ads_advert_id IN (".implode($this->getVkAdsAdvertIdsByCity($filter["VKReportCity"]), ',').")";
        }

        if ($filter["VkReportDateFrom"] && !$filter["VkReportDateTo"])
        {
            $query = $query." AND day>='".$filter["VkReportDateFrom"]."'";
        }
        if (!$filter["VkReportDateFrom"] && $filter["VkReportDateTo"])
        {
            $query = $query." AND day<='".$filter["VkReportDateTo"]."'";
        }
        if ($filter["VkReportDateFrom"] && $filter["VkReportDateTo"])
        {
            $query = $query." AND day>='".$filter["VkReportDateFrom"]."'
                              AND day<='".$filter["VkReportDateTo"]."'";
        }
        if ($filter['Ads'] == "Y")
        {
            $query = $query." GROUP BY vaa.advert_id";
        } else {
            if ($filter['Source'] == "Y")
            {
                $query = $query." GROUP BY vac.source";
            } else {
                $query = $query." GROUP BY vac.campaign_id";
            }
        }

        //сортировка по столбцам
        $sort = $request->GetProperty('Sort');
        if ($sort['clicksDesc'])
        {
            $query = $query.' ORDER BY `clicks` DESC';
        }
        if ($sort['clicksAsc'])
        {
            $query = $query.' ORDER BY `clicks` ASC';
        }
        if ($sort['spentDesc'])
        {
            $query = $query.' ORDER BY `spent` DESC';
        }
        if ($sort['spentAsc'])
        {
            $query = $query.' ORDER BY `spent` ASC';
        }
        if ($sort['impressionsDesc'])
        {
            $query = $query.' ORDER BY `impressions` DESC';
        }
        if ($sort['impressionsAsc'])
        {
            $query = $query.' ORDER BY `impressions` ASC';
        }

        $this->stats = $stmt->FetchList($query);

        for ($i=0; $i<count($this->stats); $i++)
        {
            //получение кол-во регистраций и кол-во пришедших на выставку
            if ($filter['Ads'] == "Y")
            {
                $reg_id = $this->stats[$i]['advert_id'];
                $source = $this->getUtmSourceByCampaignId($reg_id, false);
            } else {
                $reg_id = $this->stats[$i]['campaign_id'];
                $source = $this->getUtmSourceByCampaignId($reg_id);
            }
            $reg_info = $this->getRegCount($reg_id, $eventID, $filter);
            $this->stats[$i]['reg'] = $reg_info['RegCount'];
            $this->stats[$i]['come'] = $reg_info['VisitCount'];

            //получение стоимости за регистрацию и за пришедшего
            if ($this->stats[$i]['reg'] == 0)
            {
                $this->stats[$i]['CPL'] = 0;
                $this->stats[$i]['CR'] = 0;
            } else {
                $this->stats[$i]['CPL'] = number_format ($this->stats[$i]['spent'] / $this->stats[$i]['reg'], 2, ".", " ");
                $this->stats[$i]['CR'] = number_format(($this->stats[$i]['reg'] / $this->stats[$i]['clicks']) * 100, 2, ".", " ")." %";
            }
            if ($this->stats[$i]['come'] == 0)
            {
                $this->stats[$i]['spent_come'] = 0;
            } else {
                $this->stats[$i]['spent_come'] = number_format ($this->stats[$i]['spent'] / $this->stats[$i]['come'], 2, ".", " ");
            }
            if ($source == 'fb')
            {
                $this->stats[$i]['spent'] = number_format($this->stats[$i]['spent'] * 1.2, 2, ".", " ");
            } else {
                $this->stats[$i]['spent'] = number_format($this->stats[$i]['spent'], 2, ".", " ");
            }
        }

        //сортировка по регистрациям
        if ($sort['regAsc'])
        {
            $sort_array = array();
            foreach ($this->stats as $key=>$arr)
            {
                $sort_array[$key] = $arr['reg'];
            }
            array_multisort($sort_array, $this->stats,SORT_ASC);
        }
        if ($sort['regDesc'])
        {
            $sort_array = array();
            foreach ($this->stats as $key=>$arr)
            {
                $sort_array[$key] = $arr['reg'];
            }
            array_multisort($sort_array, SORT_DESC, $this->stats);
        }

        if($fullList == true)
            $this->SetItemsOnPage(0);
        else
            $this->SetItemsOnPage($onPage);
        $this->SetCurrentPage();
        $this->LoadFromArray($this->stats);
    }
}