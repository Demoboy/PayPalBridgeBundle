<?php

namespace KMJ\PayPalBridgeBundle\Service;

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

define('PP_CONFIG_PATH', sys_get_temp_dir());

class BridgeService {

    protected $clientId;
    protected $secret;

    const PAYPAL_PRODUCTION_URL = "https://api.paypal.com";
    const PAYPAL_SANDBOX_URL = "https://api.sandbox.paypal.com";

    public function __construct(array $config) {

        //create temp file and save symfony config to it
        if ($config['environment'] == "production") {
            $endpoint = self::PAYPAL_PRODUCTION_URL;
            $this->setClientId($config['production']['clientId'])
                    ->setSecret($config['production']['secret']);
        } else {
            $endpoint = self::PAYPAL_SANDBOX_URL;
            $this->setClientId($config['sandbox']['clientId'])
                    ->setSecret($config['sandbox']['secret']);
        }

        $iniConfigs = array(
            'acct1.ClientId' => $this->getClientId(),
            'acct1.ClientSecret' => $this->getSecret(),
            'service.EndPoint' => $endpoint,
            'http.ConnectionTimeOut' => $config['http']['timeout'],
            'http.Retry' => $config['http']['retry'],
            'log.FileName' => $config['logs']['filename'],
            'log.LogEnabled' => $config['logs']['enabled'],
            'log.LogLevel' => $config['logs']['level'],
        );

        @mkdir(PP_CONFIG_PATH);

        //create file in temp dir
        $fh = fopen(PP_CONFIG_PATH . '/sdk_config.ini', 'w');

        foreach ($iniConfigs as $key => $ini) {
            if ($ini === false) {
                $ini = "false";
            } else if ($ini === true) {
                $ini = "true";
            }

            fwrite($fh, "{$key}={$ini}" . PHP_EOL);
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

    public function detectCardType($cardNumber) {
        /* Validate; return value is card type if valid. */
        $false = false;
        $card_type = "";
        $card_regexes = array(
            "/^4\d{12}(\d\d\d){0,1}$/" => "visa",
            "/^5[12345]\d{14}$/" => "mastercard",
            "/^3[47]\d{13}$/" => "amex",
            "/^6011\d{12}$/" => "discover",
        );

        foreach ($card_regexes as $regex => $type) {
            if (preg_match($regex, $cardNumber)) {
                $card_type = $type;
                break;
            }
        }

        if (!$card_type) {
            return $false;
        }

        /*  mod 10 checksum algorithm  */
        $revcode = strrev($cardNumber);
        $checksum = 0;

        for ($i = 0; $i < strlen($revcode); $i++) {
            $current_num = intval($revcode[$i]);
            if ($i & 1) { /* Odd  position */
                $current_num *= 2;
            }
            /* Split digits and add. */
            $checksum += $current_num % 10;
            if
            ($current_num > 9) {
                $checksum += 1;
            }
        }

        if ($checksum % 10 == 0) {
            return $card_type;
        } else {
            return $false;
        }
    }

}