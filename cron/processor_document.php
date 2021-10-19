<?php 
require_once(dirname(__FILE__)."/processor.php");
class DocumentProcessor extends Processor
{
	public function run()
	{
		$stmt = GetStatement();
		$now = new DateTime('now', new DateTimeZone('Europe/Moscow'));
		
		$query = "SELECT o.OrderID, o.Name, o.Address, o.Email, o.Phone, o.Date, o.Time, o.Universities, b.Summ as Price
			FROM `document_order` o
			LEFT JOIN `payment_bill` b ON b.Type='document' AND b.TypeID=o.OrderID
			WHERE b.PayDate IS NOT NULL AND o.MailSent='N'";
		$result = $stmt->FetchList($query);
		
		for($i=0; $i<count($result); $i++)
		{
			$template = new Page();
			if($template->LoadByStaticPath("document-order-notification"))
			{
				$content = $template->GetProperty("Content");
				$content = str_replace("[Name]", $result[$i]["Name"], $content);
				$content = str_replace("[Address]", $result[$i]["Address"], $content);
				$content = str_replace("[Email]", $result[$i]["Email"], $content);
				$content = str_replace("[Phone]", $result[$i]["Phone"], $content);
				$content = str_replace("[Date]", $result[$i]["Date"], $content);
				$content = str_replace("[Time]", $result[$i]["Time"], $content);
				$content = str_replace("[Universities]", $result[$i]["Universities"], $content);
				$content = str_replace("[Price]", $result[$i]["Price"], $content);
				
				$theme = "Навигатор поступления: подача документов";
				SendMailFromAdmin($result[$i]["Email"], $theme, $content);
				
				$query = "UPDATE `document_order` SET MailSent='Y' WHERE OrderID=".intval($result[$i]["OrderID"]);
				$stmt->Execute($query);
					
				$this->logger->info("document-order-notification: ".$result[$i]["Email"].", OrderID=".$result[$i]["OrderID"]);
			}
		}
		return true;
	}
}
?>