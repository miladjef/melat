<?php
include  'mellat.php';
$mellat = new MellatBank();
if (!isset($_POST["ResCode"])) {
    $mellat->startPayment(1000, "http://example.com/callback.php", "description for payment");
}else {
//check payment
        $results = $mellat->checkPayment($_POST);
        if ($results['status'] == 'success') {
            //payment success
            $ref_id = $results['trans'];
        } else {
            //payment error

        }
}