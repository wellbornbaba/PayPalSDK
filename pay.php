<?php

$invoiceorder = $_POST['invoiceid'];
$paypalamount = $_POST['amount'];
$returnurl = "";
$cancelurl = "";
$res = ["msg" => "", "status" => false];

// needed sandbox clientid and clientsecret
$clientid = "PAYPAL_CLIENTID";
$clientsecret = "PAYPAL_CLIENTSECRET";

include "PayPalSDKPaymentProcessor.php";
// Create an instance of the PayPalPaymentProcessor class
$paypalProcessor = new PayPalSDKPaymentProcessor(false);
// for demo sandbox testing
$paypalProcessor->setDemo($clientid, $clientsecret);
// for live production enable this and disable setdemo method
// $paypalProcessor->setLive($clientid, $clientsecret) ;
// Example: Create a payment
$paymentResponse = $paypalProcessor->createPayment(
    $invoiceorder,
    $paypalamount,
    $returnurl,
    $cancelurl
);

if ($paymentResponse) {
    $res['status'] = true;
    $res['msg'] = $paymentResponse;
} else {
    $res['msg'] = '<div class="alert alert-danger">Error occurred payment not processed</div>';
}

echo json_encode($res);
?>
