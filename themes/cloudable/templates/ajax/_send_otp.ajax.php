<?php 



use Twilio\Rest\Client;

$AuthUser = Auth::getAuth();
$db = Database::getDatabase();

// setup result array
$rs = array();
$rs['error'] = '';
$rs['otp_status'] = 'failed';
$rs["redirect_url"] = WEB_ROOT.'/two-step-verification.'.SITE_CONFIG_PAGE_EXTENSION;

$sid    = "AC333c51b2dc9a7a14423c849ca1188048";
$token  = "2118d929443499a61a48d8a29bed3887";
// $token  = "211VNhYTiBXpadG9niCesy3ajakmMmzPA7";

$mobilenumber = $AuthUser->contact_no;
$mobilenumber = str_replace(" ", "", $mobilenumber);
$mobilenumber = str_replace("(", "", $mobilenumber);
$mobilenumber = str_replace(")", "", $mobilenumber);
$mobilenumber = str_replace("-", "", $mobilenumber);
// print_r($mobilenumber);
// die();
$rndno=rand(100000, 999999);
$_SESSION['randnumber'] = $rndno;
// $message = urlencode("otp number is ".$rndno);

$twilio = new Client($sid, $token);
try{
    $message = $twilio->messages
                  ->create($mobilenumber, // to
                          [
                              "body" => 'Hi there ! Your FonnBox OTP is '.$rndno,
                              "from" => "FonnBox"
                            //   "from" => "+16263827815"
                          ]
                  );
    $rs['otp_status'] = 'success';
    // $rs["otp"] = $rndno;

}catch(Exception $e){
    if ($e->getCode() == 21612){
        $message = $twilio->messages
                  ->create($mobilenumber, // to
                          [
                              "body" => 'Hi there ! Your FonnBox OTP is '.$rndno,
                              "from" => "+16263827815"
                          ]
                          );
        $rs['otp_status'] = 'success';
        // $rs["otp"] = $rndno;
    }
    else{
        $rs["error"] = t("something_went_wrong", "Something went wrong");
    }
}

echo json_encode($rs);

?>