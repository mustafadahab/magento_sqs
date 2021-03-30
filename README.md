# magento_sqs
This is a way to link Magento with an other API through AWS Amazon Simple Queue Service (SQS).

## Test Case
This example to send order data to another endpoint listing for (sales_order_place_after) event


## Requirements

AWS SDK for PHP needs to be installed on the Magento root directory.

```bash
composer require aws/aws-sdk-php
```


## Installation

1- Create A directory (Mustafa) at the following path 


```bash
/app/code/local/Mustafa
```

2- Create XML file at the following path

```bash
Mustafa/Checkout/etc/config.xml
```



```bash
<?xml version="1.0"?>
<config>
    <modules>
        <Mustafa_Checkout>
            <version>0.1.3</version>
        </Mustafa_Checkout>
    </modules>
    <global>
        <models>
            <Mustafa>
                <class>Mustafa_Checkout_Model</class>
            </Mustafa>
        </models>
            <events>
                <sales_order_place_after>
                    <observers>
                        <Mustafa_Checkout_Model_Observer>
                            <class>Mustafa_Checkout_Model_Observer</class>
                            <method>orderSms</method>
                            <type>singleton</type>
                        </Mustafa_Checkout_Model_Observer>
                    </observers>
                </sales_order_place_after>
            </events>

    </global>
</config>
```


3- Create PHP file at the following path

```bash
Mustafa/Checkout/Model/Observer.php
```



```bash
<?php
require 'vendor/autoload.php';

use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

class Mustafa_Checkout_Model_Observer
{
    public function orderSms(Varien_Event_Observer $observer)
    {

        // connect with AWS SQS using IAM
        $client = new SqsClient([
            'version'     => 'latest',
            'region'      => 'us-east-2',
            'credentials' => [
                'key'    => 'XXXX',
                'secret' => 'SecretXXX',
            ]]);


        // mongo order data you want to send
        $order              = $observer->getEvent()->getOrder();

        $paymentMethodCode  = $order->getPayment()->getMethodInstance()->getCode();
        $incrementId        = $order->getIncrementId();
        $custName           = $order->getCustomerFirstname();
        $customerId         = $order->getCustomerId();
        $orderPrice         = $order->getGrandTotal();
        $orderId            = $order->getId();
        $mobile             = trim($order->getShippingAddress()->getData('telephone'));


         $dataToSend = [
                 "customer" => [
                     "id"      => $customerId,
                     "name"    => $custName
                 ],
                 "order" => [
                     "id"               => $orderId,
                     "price"            => $orderPrice,
                     "payment_method"   => $paymentMethodCode
                 ]
         ];

        $jsondData = json_encode($dataToSend);


        // update your SQS link
        $queueUrl = "https://sqs.us-east-2.amazonaws.com/XXXXX/sqsName";

        // preapare sqs data 
        // update domain.com to your domain
        // update the api endpoint
        $params = [
            'MessageAttributes' => [
                "domain" => [
                    'DataType' => "String",
                    'StringValue' => "domain.com"
                ],
                "api" => [
                    'DataType' => "String",
                    'StringValue' => "/api/"
                ]
            ],
            'MessageBody'   => $jsondData,
            'QueueUrl'      => $queueUrl
        ];

        try {
            // send data to SQS by the client

            $result = $client->sendMessage($params);
            //var_dump($result);
        } catch (AwsException $e) {
            // output error message if fails
            //var_dump($e->getMessage());
        }

    }
}
```


