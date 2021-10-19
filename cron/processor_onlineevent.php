<?php 
require_once(dirname(__FILE__)."/processor.php");
class OnlineEventProcessor extends Processor
{
	public function run()
	{
	    //$this->notify4HourEmail();
	    //$this->notify1HourSMS();
	    //$this->notify5MinutesSMS();
		return true;
	}
	
	private function notify4HourEmail()
	{
	    $result = $this->getData('+4 hours', 1);
	    
	    $fmt = new IntlDateFormatter(
	        'ru_RU',
	        IntlDateFormatter::FULL,
	        IntlDateFormatter::FULL,
	        'Europe/Moscow',
	        IntlDateFormatter::GREGORIAN
	        );
	    $fmt->setPattern('d MMMM в HH:mm');
	    
	    $stmt = GetStatement();
	    for($i=0; $i<count($result); $i++)
	    {
	        $template = new Page();
	        $template->LoadByStaticPath("onlineevent-4-hour-notification");
	        $content = $template->GetProperty("Content");
	        $content = str_replace("[Title]", $result[$i]["Title"], $content);
	        $content = str_replace("[Description]", $result[$i]["Description"], $content);
	        $content = str_replace("[FinishedDate]", $fmt->format(new DateTime($result[$i]["EventDateTime"], new DateTimeZone('Europe/Moscow'))), $content);
	        $content = str_replace("[OnlineEventID]", $result[$i]["OnlineEventID"], $content);
	        
	        $theme = "Навигатор поступления: напоминание о вебинаре";
	        if($template->GetProperty("Description"))
	        {
	            $theme = $template->GetProperty("Description");
	        }
	        SendMailFromAdmin($result[$i]["UserEmail"], $theme, $content);
	        
	        $query = "UPDATE `data_online_event2user` SET Notification=1 WHERE OnlineEventID=".intval($result[$i]["OnlineEventID"])." AND UserItemID=".intval($result[$i]["UserID"]);
	        $stmt->Execute($query);
	        
	        $this->logger->info("onlineevent-4-hour-notification: ".$result[$i]["UserEmail"].", OnlineEventID=".$result[$i]["OnlineEventID"]);
	    }
	}
	
	private function notify1HourSMS()
	{
	    $result = $this->getData('+1 hours', 2);
	    
	    $fmt = new IntlDateFormatter(
	        'ru_RU',
	        IntlDateFormatter::FULL,
	        IntlDateFormatter::FULL,
	        'Europe/Moscow',
	        IntlDateFormatter::GREGORIAN
	        );
	    $fmt->setPattern('HH:mm');
	    
	    $shortLinks = array();
	    
	    $stmt = GetStatement();
	    for($i=0; $i<count($result); $i++)
	    {
	        $phone = $result[$i]["UserPhone"];
	        $phone = preg_replace("/[^0-9]/", "", $phone);
	        if(substr($phone, 0, 1) != "7") $phone = "7".substr($phone, 1);
	        
	        $eventID = $result[$i]["OnlineEventID"];
	        if(!isset($shortLinks[$eventID]))
	        {
	            $link = "https://propostuplenie.ru/events/".$eventID."-".$result[$i]["StaticPath"]."/?utm_source=sms&utm_medium=sms&utm_campaign=".$eventID."_1h";
	            $shortLinks[$eventID] = GetShortURL($link);
	        }
	        $text = "Вебинар \"".$result[$i]["Title"]."\" начнется в ".$fmt->format(new DateTime($result[$i]["EventDateTime"], new DateTimeZone('Europe/Moscow')))." по Мск. Смотрите по ссылке: ".$shortLinks[$eventID];
	        if(SendSMSFromAdmin($phone, $text))
	        {
	            $query = "UPDATE `data_online_event2user` SET Notification=2 WHERE OnlineEventID=".intval($result[$i]["OnlineEventID"])." AND UserItemID=".intval($result[$i]["UserID"]);
	            $stmt->Execute($query);
	            
	            $this->logger->info("onlineevent-1-hour-sms: ".$phone.", OnlineEventID=".$result[$i]["OnlineEventID"]);
	        }
	    }
	}

	private function notify5MinutesSMS()
	{
	    $result = $this->getData('+5 minutes', 3);

	    $stmt = GetStatement();
	    for($i=0; $i<count($result); $i++)
	    {
	        $phone = $result[$i]["UserPhone"];
	        $phone = preg_replace("/[^0-9]/", "", $phone);
	        if(substr($phone, 0, 1) != "7") $phone = "7".substr($phone, 1);
	        $eventID = $result[$i]["OnlineEventID"];
	        $text = "Вебинар начнется через несколько минут. Подключайтесь!";
	        if(SendSMSFromAdmin($phone, $text))
	        {
	            $query = "UPDATE `data_online_event2user` SET Notification=3 WHERE OnlineEventID=".intval($result[$i]["OnlineEventID"])." AND UserItemID=".intval($result[$i]["UserID"]);
	            $stmt->Execute($query);
	            
	            $this->logger->info("onlineevent-5-minutes-sms: ".$phone.", OnlineEventID=".$result[$i]["OnlineEventID"]);
	        }
	    }
	}
	
	private function getData($period, $number)
	{
	    $stmt = GetStatement();
	    $now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
	    
	    $query = "SELECT e.*, u.UserID, u.UserEmail, u.UserPhone
			FROM `data_online_event2user` e2u
			LEFT JOIN `data_online_event` e ON e2u.OnlineEventID=e.OnlineEventID
			LEFT JOIN `users_item` u ON e2u.UserItemID=u.UserID
			WHERE e.Active='Y' AND e.EventDateTime > ".Connection::GetSQLString($now->format('Y-m-d H:i:s'))." AND e.EventDateTime < ".Connection::GetSQLString($now->modify($period)->format('Y-m-d H:i:s'))." AND e2u.Notification < ".$number;
//	    $this->logger->info($query);

	    return $stmt->FetchList($query);
	}
}
?>