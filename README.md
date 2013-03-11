KMJPayPalBridgeBundle
================================


Welcome to the KMJPayPalBridgeBundle. The goal of this bundle is to provide an easy way to integrate the Paypal SDK into a Symfony project.


1) Installation
----------------------------------

KMJPayPalBridgeBundle can conveniently be installed via Composer. Just add the following to your composer.json file:

<pre>
// composer.json
{
    // ...
    require: {
        // ..
        "paypal/rest-api-sdk-php": "dev-master",
        "kmj/paypalbridgemaster": "dev-master"

    }
}
</pre>


Then, you can install the new dependencies by running Composer's update command from the directory where your composer.json file is located:

<pre>
    php composer.phar update
</pre>


Now, Composer will automatically download all required files, and install them for you. All that is left to do is to update your AppKernel.php file, and register the new bundle:

<pre>
// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new KMJ\PayPalBridgeBundle\KMJPayPalBridgeBundle(),
    // ...
);
</pre>



2) Usage
----------------------------------

The KMJPayPalBridgeBundle is called as a standard service.

<pre>
$this->get('paypal')
</pre>

This returns a service that sets the paypal SDK with the proper ini settings. 
The service also contains a valid PayPal\Rest\ApiContext object that can be passed to other PayPal objects.
This bundle also automatically swtiches the bundle based on the environment. The production environment is the only environment that gets the production endpoint for PayPal


3) Configuration
----------------------------------

kmj_pay_pal_bridge:
    clientId:                                           //Client Id provided from developer.paypal.com
    secret:                                             //Client Secret provided from developer.paypal.com
    logs:
        enabled: true                                   //Should logs be used
        filename: %kernel.root_dir%/logs/paypal.log     //the location for the log file
        level: fine                                     //level of log reporting
    http:
        timeout: 30                                     //The http timeout before an error is generated
        retry: true                                     //Should the request be tried again if timeout is reached