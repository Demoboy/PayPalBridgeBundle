<?php

namespace KMJ\PayPalBridgeBundle\Service;

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

define('PP_CONFIG_PATH', __DIR__.'/../Resources/tmp');

class BridgeService {

    protected $clientId;
    protected $secret;

    public function __construct(array $config, $kernel) {
        $this->setClientId($config['clientId'])
                ->setSecret($config['secret']);

        $iniConfigs = array(
            'acct1.ClientId' => $this->getClientId(),
            'acct1.ClientSecret' => $this->getSecret(),
            'http.ConnectionTimeOut' => $config['http']['timeout'],
            'http.Retry' => $config['http']['retry'],
            'log.FileName' => $config['logs']['filename'],
            'log.LogEnabled' => $config['logs']['enabled'],
            'log.LogLevel' => $config['logs']['level'],
        );

        //create temp file and save symfony config to it
        if ($kernel->getEnvironment() == "prod") {
            $iniConfigs['service.EndPoint'] = "https://api.paypal.com";
        } else {
            $iniConfigs['service.EndPoint'] = "https://api.sandbox.paypal.com";
        }

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