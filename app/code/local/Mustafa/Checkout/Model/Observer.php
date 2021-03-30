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
                'key'    => 'AKIAXTBWCVNNVDOE3Y76',
                'secret' => '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',
            ]]);


        // mongo order data
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



        $queueUrl = "https://sqs.us-east-2.amazonaws.com/521951947611/magento_orders";

        // preapare sqs data
        $params = [
            'MessageAttributes' => [
                "domain" => [
                    'DataType' => "String",
                    'StringValue' => "3.141.170.231"
                ],
                "api" => [
                    'DataType' => "String",
                    'StringValue' => "/api/test.php"
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