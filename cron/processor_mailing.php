<?php 
require_once(dirname(__FILE__)."/processor.php");
class MailingProcessor extends Processor
{
	public function run()
	{
		$stmt = GetStatement();
		$now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
		
		$fmt = new IntlDateFormatter(
				'ru_RU',
				IntlDateFormatter::FULL,
				IntlDateFormatter::FULL,
				'Europe/Moscow',
				IntlDateFormatter::GREGORIAN
		);
		$fmt->setPattern('d MMMM в HH:mm');
		
		//1. process new mailing
		$query = "SELECT m.MailingID, m.Emails, m.Time, m.Status
			FROM `mailing_mailing` m
			WHERE m.Time <= ".Connection::GetSQLString($now->format('Y-m-d H:i:s'))." AND m.Status='confirmed'";
		$result = $stmt->FetchList($query);	
		for($i=0; $i<count($result); $i++)
		{
			$emailList = explode(PHP_EOL, $result[$i]['Emails']);
			for($j=0; $j<count($emailList); $j++)
			{
				$exist = $stmt->FetchField("SELECT count(*) FROM `mailing_history` 
						WHERE MailingID=".intval($result[$i]['MailingID'])." AND Email=".Connection::GetSQLString($emailList[$j]));
				if($exist == 0)
				{
					$stmt->Execute("INSERT INTO `mailing_history` SET
							MailingID=".intval($result[$i]['MailingID']).",
							Email=".Connection::GetSQLString($emailList[$j]).",
							Status='new',
							Created=".Connection::GetSQLString(date("Y-m-d H:i:s"))
					);
				}
			}
			$stmt->Execute("UPDATE `mailing_mailing` SET Status='inprogress' WHERE MailingID=".intval($result[$i]['MailingID']));
			$this->logger->info("mailing-addtoorder: MailingID=".$result[$i]['MailingID'].", EmailCount=".count($emailList));
		}
		
		//2. process mailing
		$query = "SELECT h.HistoryID, h.Email, m.From, m.Theme, m.Text
			FROM `mailing_history` h
			LEFT JOIN `mailing_mailing` m ON h.MailingID=m.MailingID
			WHERE h.Status='new' LIMIT 200";
		$result = $stmt->FetchList($query);
		for($i=0; $i<count($result); $i++)
		{
			$mailResult = SendMailFromAdmin($result[$i]["Email"], $result[$i]["Theme"], $result[$i]["Text"], array(), $result[$i]["From"]);
			if($mailResult === true)
			{
				$stmt->Execute("UPDATE `mailing_history` SET Status='done' WHERE HistoryID=".intval($result[$i]['HistoryID']));
			}
			else 
			{
				$stmt->Execute("UPDATE `mailing_history` SET Status='fail' WHERE HistoryID=".intval($result[$i]['HistoryID']));
			}
		}
		
		//3. process complete status
		$query = "SELECT m.MailingID, SUM(h.Status='new') StatusNew, SUM(h.Status='done') StatusDone, SUM(h.Status='fail') StatusFail
			FROM `mailing_mailing` m
			LEFT JOIN `mailing_history` h ON m.MailingID=h.MailingID
			WHERE m.Status='inprogress'
			GROUP BY m.MailingID";
		$result = $stmt->FetchList($query);
		for($i=0; $i<count($result); $i++)
		{
			if($result[$i]['StatusNew'] == 0)
			{
				$stmt->Execute("UPDATE `mailing_mailing` SET Status='complete' WHERE MailingID=".intval($result[$i]['MailingID']));
				$this->logger->info("mailing-complete: MailingID=".$result[$i]['MailingID'].", Success=".intval($result[$i]['StatusDone']).", Fail=".intval($result[$i]['StatusFail']));
			}
		}
		
		return true;
	}
}
?>