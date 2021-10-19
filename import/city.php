<?php
 
/**
 * Возвращает информация об IP адресе
 */
function get_ip_info($ip)
{
    $postData = "
        <ipquery>
            <fields>
                <all/>
            </fields>
            <ip-list>
                <ip>$ip</ip>
            </ip-list>
        </ipquery>
    "; 
 
    $curl = curl_init(); 
 
    curl_setopt($curl, CURLOPT_URL, 'http://194.85.91.253:8090/geo/geo.html');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
 
    $responseXml = curl_exec($curl);
    curl_close($curl);
 
    if (substr($responseXml, 0, 5) == '<?xml')
    {
        $ipinfo = new SimpleXMLElement($responseXml);
        return $ipinfo->ip;
    }
 
    return false;
}
 
// пример использования
$ip = "178.49.53.116";
$ipinfo = get_ip_info($ip);
print_r($ipinfo);
 
?>