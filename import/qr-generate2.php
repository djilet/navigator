<?php 
require_once(dirname(__FILE__) . "/../include/init.php");

$ID=100000000;

for($ID=100031000; $ID<100035001; $ID++)
{
    /*$url = 'http://barcode.tec-it.com/barcode.ashx?data=';
    $data = 'https://propostuplenie.ru/exhibition?Registration=';
    $src = $url . $data . $ID . '&code=QRCode&dpi=300';*/
    
    $url='https://chart.googleapis.com/chart?chs=500x500&cht=qr&choe=UTF-8&chl=';
    $data = 'https://propostuplenie.ru/exhibition?Registration=';
    $src = $url . $data . $ID;
    
    $ch = curl_init($src);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $file = curl_exec($ch);
    $dir = dirname(__FILE__) . "/../var/pdf/";
    $imgPath = $dir.'output'.$ID.'.png';
    file_put_contents($imgPath, $file);
}

?>