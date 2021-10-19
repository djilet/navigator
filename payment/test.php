<?php 

require_once(dirname(__FILE__)."/../include/init.php");

$shopID = "149836";
$scid = "556139";

?>
<html>
	<form action="https://demomoney.yandex.ru/eshop.xml" method="post">
	    <input name="shopId" value="<?php echo $shopID;?>" type="hidden"/>
	    <input name="scid" value="<?php echo $scid;?>" type="hidden"/>
	    <input name="sum" value="2800" type="hidden">
	    <input name="orderNumber" value="2" type="hidden"/>
	    <input name="ym_merchant_receipt" value='{"customerContact":"+79139300001","items":[{"quantity":1,"price":{"amount":2800},"tax":3,"text":"\u041a\u043e\u043d\u0441\u0443\u043b\u044c\u0442\u0430\u0446\u0438\u044f"}]}' type="hidden"/>
	    <input type="submit" value="Заплатить"/>
	</form>
	
</html>