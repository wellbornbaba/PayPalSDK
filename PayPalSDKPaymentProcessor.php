<?php 

class PayPalSDKPaymentProcessor
{
    protected $authUrlRoot = "https://api-m.sandbox.paypal.com";
    protected $clientId = "";
    protected $clientSecret = "";
    private $accessToken;

    public function __construct($isLive = false )
    {
        // if ($isLive) {
        //     $this->authUrlRoot = "https://api-m.paypal.com";
        //     $this->setLive(
        //         "",
        //         ""
        //     );

        // } else {
            $this->setDemo();
        // }

        $this->accessToken = $this->getAccessToken();
        if (!$this->accessToken) {
            exit("Failed to retrieve access token.");
        }
    }

    public function setLive($live_clientid, $live_clientSecret)
    {
        /**
         * note live client and secret is different from sandbox 
         * check this link after login 
         * https://developer.paypal.com/dashboard/applications
         * toggle the Sandbox to LIVE
         */
        $this->authUrlRoot = "https://api-m.paypal.com";
        $this->clientId = $live_clientid;
        $this->clientSecret = $live_clientSecret;

    }

    public function setDemo($demo_clientid="", $demo_clientSecret="")
    {
        /**
         * visit this link after login to generate your sandbox
         * https://developer.paypal.com/dashboard/applications
         * toggle the Sandbox oFF
         */
        $this->authUrlRoot = "https://api-m.sandbox.paypal.com";
        if($demo_clientid) $this->clientId = $demo_clientid;
        if($demo_clientSecret) $this->clientSecret = $demo_clientSecret;
    }

    public function createPayment($invoiceNo, $amount, $returnUrl = "", $cancelUrl = "")
    {
        if (empty($amount) || !is_numeric($amount)) {
            exit("Invalid amount.");
        }

        $authUrl = $this->authUrlRoot . "/v2/checkout/orders";
        $authHeaders = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->accessToken,
            "PayPal-Request-Id: " . ($invoiceNo ? $invoiceNo : uniqid())
        ];

        $data = json_encode([
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $amount
                    ],
                    "payment_method" => "paypal"
                ]
            ],
            "payment_source" => [
                "paypal" => [
                    "experience_context" => [
                        "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                        "brand_name" => "Wehostz LTD - Order $invoiceNo",
                        "locale" => "en-US",
                        "landing_page" => "LOGIN",
                        "user_action" => "PAY_NOW",
                        "return_url" => $returnUrl,
                        "cancel_url" => $cancelUrl
                    ]
                ]
            ]
        ]);

        $res = $this->execute($authUrl, $authHeaders, $data);
        // printit($res);
        if ($res['headstatus'] != 200) {
            exit("Failed to create payment.");
        }
        
        $responseData = json_decode($res['response'], true);
         $checkoutNowHref = "";

        if (isset($responseData['links'])) {
            foreach ($responseData['links'] as $link) {
                if ($link['rel'] === 'payer-action') {
                    $checkoutNowHref = $link['href'];
                    break;
                }
            }
        }

        return $checkoutNowHref;
    }

    protected function getAccessToken()
    {
        $authUrl = $this->authUrlRoot . "/v1/oauth2/token";
        $authCredentials = base64_encode($this->clientId . ":" . $this->clientSecret);
        $authHeaders = [
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic " . $authCredentials
        ];
        $authData = "grant_type=client_credentials";

        $res = $this->execute($authUrl, $authHeaders, $authData);

        if ($res['headstatus'] != 200) {
            exit("Failed to retrieve access token.");
        }

        $authData = json_decode($res['response'], true);
        return $authData['access_token'];
    }

    protected function execute($authUrl, $authHeaders, $authData = "")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $authData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $authResult = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ["response" => $authResult, "headstatus" => $httpStatus];
    }
}

?>
