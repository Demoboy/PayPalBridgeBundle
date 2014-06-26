<?php

namespace KMJ\PayPalBridgeBundle\Service;

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

if (!defined('PP_CONFIG_PATH')) {
    define('PP_CONFIG_PATH', sys_get_temp_dir());
}

/**
 * Class BridgeService
 */
class BridgeService
{

    protected $clientId;
    protected $secret;
    protected $mode;
    protected $config;

    const PAYPAL_PRODUCTION_URL = "https://api.paypal.com";
    const PAYPAL_SANDBOX_URL = "https://api.sandbox.paypal.com";
    const PAYPAL_SANDBOX_MODE = "sandbox";
    const PAYPAL_PRODUCTION_MODE = "live";

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        if ($config['environment'] == "production") {
            $endpoint = self::PAYPAL_PRODUCTION_URL;
            $this->setClientId($config['production']['clientId'])
                    ->setSecret($config['production']['secret']);
            $this->setMode(self::PAYPAL_PRODUCTION_MODE);
        } else {
            $endpoint = self::PAYPAL_SANDBOX_URL;
            $this->setClientId($config['sandbox']['clientId'])
                    ->setSecret($config['sandbox']['secret']);
            $this->setMode(self::PAYPAL_SANDBOX_MODE);
        }

        $iniConfigs = array(
            'service.EndPoint' => $endpoint,
            "mode" => $this->getMode(),
            'http.ConnectionTimeOut' => $config['http']['timeout'],
            'http.Retry' => $config['http']['retry'],
            'log.FileName' => $config['logs']['filename'],
            'log.LogEnabled' => $config['logs']['enabled'],
            'log.LogLevel' => $config['logs']['level'],
        );

        $this->setConfig($config);

        //create temp file and save symfony config to it
        if ($config['options']['create_config_file']) {
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
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param string $clientId
     *
     * @return $this
     */
    private function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @param string $secret
     *
     * @return $this
     */
    private function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @param string $mode
     *
     * @return $this
     */
    private function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param array $config
     */
    private function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns ready to use ApiContext from PayPal sdk
     *
     * @return ApiContext
     */
    public function getApiContext()
    {
        $cred = new OAuthTokenCredential($this->getClientId(), $this->getSecret());
        $apiContext = new ApiContext($cred, 'Request' . time());

        $apiContext->setConfig(array(
            "mode" => $this->getMode(),
            'http.ConnectionTimeOut' => $this->config['http']['timeout'],
            'log.LogEnabled' => (bool) $this->config['logs']['enabled'],
            'log.FileName' => $this->config['logs']['filename'],
            'log.LogLevel' => $this->config['logs']['level'],
        ));

        return $apiContext;
    }

    /**
     * Returns card type name if valid or false otherwise
     *
     * @param string $cardNumber
     *
     * @return bool|string
     */
    public function detectCardType($cardNumber)
    {
        /* Validate; return value is card type if valid. */
        $false = false;
        $cardType = "";
        $cardRegexes = array(
            "/^4\d{12}(\d\d\d){0,1}$/" => "visa",
            "/^5[12345]\d{14}$/" => "mastercard",
            "/^3[47]\d{13}$/" => "amex",
            "/^6011\d{12}$/" => "discover",
        );

        foreach ($cardRegexes as $regex => $type) {
            if (preg_match($regex, $cardNumber)) {
                $cardType = $type;
                break;
            }
        }

        if (!$cardType) {
            return $false;
        }

        /*  mod 10 checksum algorithm  */
        $revcode = strrev($cardNumber);
        $checksum = 0;

        for ($i = 0; $i < strlen($revcode); $i++) {
            $currentNum = intval($revcode[$i]);
            /* Odd  position */
            if ($i & 1) {
                $currentNum *= 2;
            }
            /* Split digits and add. */
            $checksum += $currentNum % 10;
            if ($currentNum > 9) {
                $checksum += 1;
            }
        }

        if ($checksum % 10 == 0) {
            return $cardType;
        } else {
            return $false;
        }
    }

}
