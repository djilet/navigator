<?php
require_once dirname(__FILE__) . "/../vendor/autoload.php";
include 'swagger_client/lib/Configuration.php';
include 'swagger_client/lib/Api/RegistrationApi.php';
include 'swagger_client/lib/HeaderSelector.php';
include 'swagger_client/lib/ApiException.php';
include 'swagger_client/lib/ObjectSerializer.php';
include 'swagger_client/lib/Model/CreateRegistrationRequest.php';
include 'swagger_client/lib/Model/CreateRegistrationResponse.php';

class Swagger
{
    private $msToken;
    private $usersCount = 1;

    public function __construct()
    {
        $this->msToken = $this->getMicrosoftToken();
    }

    public function sendVisitToCRM($CrmRegistrationId)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, GetFromConfig('CompanyUrl', 'crmData') . "/api/Registration/$CrmRegistrationId/UpdateStatus/true");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        $headers = array();
        $headers[] = 'Accept: */*';
        $headers[] = 'Authorization: bearer ' . $this->msToken;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        curl_close($ch);

        if (!isset(json_decode($result)->status)) {
            $this->pushLog("SUCCESS: " . $CrmRegistrationId . "\r\n" . $result);
        } else {
            $error = json_decode($result)->errors->registrationId[0];
            $this->pushLog("ERROR: " . $CrmRegistrationId . "\r\n" . $error);
        }
    }

    /**
     * @return string registration id
     */
    public function sendFamilyToCRM($data, $userId, $guid)
    {
        // Configure OAuth2 access token for authorization: oauth2
        $config = Swagger\Client\Configuration::getDefaultConfiguration()
            ->setAccessToken($this->msToken)
            ->setHost(GetFromConfig('CompanyUrl', 'crmData'));

        $apiInstance = new Swagger\Client\Api\RegistrationApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new GuzzleHttp\Client(),
            $config
        );
        if (isset($data['second_parent']['phone'])) {
            $data['second_parent']['phone'] = implode('', explode(' ', $data['second_parent']['phone']));
        }
        $data["campaign_code"] = $guid;
        $body = new \Swagger\Client\Model\CreateRegistrationRequest($data); // \Swagger\Client\Model\CreateRegistrationRequest | Данные регистрации
        try {
            $result = $apiInstance->apiRegistrationPost($body);
            $this->pushLog('CRMRegistrationId ' . json_decode($result)->registrationId . "\r\n" . "RegistrationID: " . implode(', ', $userId));
            return ($result);
        } catch (Exception $e) {
            $this->pushLog('userId ' . implode(', ', $userId) . "\r\n" . 'Exception when calling RegistrationApi->apiRegistrationPost: ' . $e->getMessage() . " " . PHP_EOL);
        }

    }

    /**
     * @return string registration id
     */
    public function sendFamilyToCRMScriptVersion($data, $userId, $guid)
    {
        
        if ($this->usersCount == 100) {
            $this->msToken = $this->getMicrosoftToken();
            $this->usersCount = 0;
        }
        $this->usersCount += 1;

        // Configure OAuth2 access token for authorization: oauth2
        $config = Swagger\Client\Configuration::getDefaultConfiguration()
            ->setAccessToken($this->msToken)
            ->setHost(GetFromConfig('CompanyUrl', 'crmData'));

        $apiInstance = new Swagger\Client\Api\RegistrationApi(
            // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
            // This is optional, `GuzzleHttp\Client` will be used as default.
            new GuzzleHttp\Client(),
            $config
        );

        if (isset($data['second_parent']['phone'])) {
            $data['second_parent']['phone'] = implode('', explode(' ', $data['second_parent']['phone']));
        }

        $data["campaign_code"] = $guid;
        $body = new \Swagger\Client\Model\CreateRegistrationRequest($data); // \Swagger\Client\Model\CreateRegistrationRequest | Данные регистрации
        try {
            $result = $apiInstance->apiRegistrationPost($body);
            $this->pushLog('CRMRegistrationId ' . json_decode($result)->registrationId . "\r\n" . "RegistrationID: " . implode(', ', $userId));
            return ($result);
        } catch (Exception $e) {
            $this->pushLog('userId ' . implode(', ', $userId) . "\r\n" . 'Exception when calling RegistrationApi->apiRegistrationPost: ' . $e->getMessage() . " " . PHP_EOL);
            print_r("wrong user");
        }

    }

    /**
     * @return string microsoft token
     */
    private function getMicrosoftToken()
    {
        $microsoftUrl = GetFromConfig('MicrosoftUrl', 'crmData');
        $data = [
            'scope' => GetFromConfig('Scope', 'crmData'),
            'grant_type' => GetFromConfig('GrantType', 'crmData'),
            'client_id' => GetFromConfig('ClientId', 'crmData'),
            'client_secret' => GetFromConfig('ClientSecret', 'crmData'),
        ];

        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($microsoftUrl, false, $context);
        return json_decode($result)->access_token;
    }

    /**
     * @return array time, CRMRegistrationId, status
     */
    private function pushLog($data)
    {
        $path = dirname(__FILE__) . '/../var/log/data.log';

        $stringToWrite = 'STARTED AT: ' . date("Y-m-d H:i:s") . "\r\n";

        $stringToWrite .= $data . "\r\n";

        $stringToWrite .= "----------------------------------------------------------------------\r\n";

        $fp = fopen($path, 'a');

        fwrite($fp, $stringToWrite);
        fclose($fp);
    }
}
