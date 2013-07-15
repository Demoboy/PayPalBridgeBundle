<?php

namespace KMJ\PayPalBridgeBundle\Service;

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

define('PP_CONFIG_PATH', tempnam(sys_get_temp_dir(), ""));

class BridgeService {

    protected $clientId;
    protected $secret;
    
    const PAYPAL_PRODUCTION_URL = "https://api.paypal.com";
    const PAYPAL_SANDBOX_URL = "https://api.sandbox.paypal.com";
    

    public function __construct(array $config, $kernel) {
        //create temp file and save symfony config to it
        if ($config['enviroment'] == "production") {
            $iniConfigs['service.EndPoint'] = self::PAYPAL_PRODUCTION_URL;
            $this->setClientId($config['production']['clientId'])
                ->setSecret($config['production']['secret']);
        } else {
            $iniConfigs['service.EndPoint'] = self::PAYPAL_SANDBOX_URL;
            $this->setClientId($config['sandbox']['clientId'])
                ->setSecret($config['sandbox']['secret']);
        }
        
         $iniConfigs = array(
            'acct1.ClientId' => $this->getClientId(),
            'acct1.ClientSecret' => $this->getSecret(),
            'http.ConnectionTimeOut' => $config['http']['timeout'],
            'http.Retry' => $config['http']['retry'],
            'log.FileName' => $config['logs']['filename'],
            'log.LogEnabled' => $config['logs']['enabled'],
            'log.LogLevel' => $config['logs']['level'],
        );

        //create file in temp dir
        $fh = fopen(PP_CONFIG_PATH . '/sdk_config.ini', 'w');
        
        foreach ($iniConfigs as $key => $ini) {
            if ($ini === false) {
                $ini = "false";
            } else if ($ini === true) {
                $ini = "true";
            }

            fwrite($fh, "{$key}={$ini}".PHP_EOL);
        }
        
        fclose($fh);
    }

    public function getClientId() {
        return $this->clientId;
    }

    public function getSecret() {
        return $this->secret;
    }

    private function setClientId($clientId) {
        $this->clientId = $clientId;
        return $this;
    }

    private function setSecret($secret) {
        $this->secret = $secret;
        return $this;
    }

    public function getApiContext() {
        $cred = new OAuthTokenCredential($this->getClientId(), $this->getSecret());
        return new ApiContext($cred, 'Request' . time());
    }
}