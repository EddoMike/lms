<?php

function sendSms($username, $password, $senderId, $destination, $message) {
    // API URLs
    $tokenUrl = "https://sewmr-sms.sewmr.com/api/auth/generate-token/";
    $smsUrl = "https://sewmr-sms.sewmr.com/api/messaging/send-sms/";

    // Generate Access Token
    $credentials = base64_encode($username . ":" . $password);

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic " . $credentials,
        "Content-Type: application/json"
    ]);

    $tokenResponse = curl_exec($ch);

    if ($tokenResponse === false) {
        return json_encode([
            "success" => false,
            "message" => "cURL Error: " . curl_error($ch)
        ]);
    }

    curl_close($ch);

    $tokenData = json_decode($tokenResponse, true);

    if (!isset($tokenData['success']) || $tokenData['success'] !== true) {
        return json_encode([
            "success" => false,
            "message" => "Failed to generate token: " . $tokenData['message']
        ]);
    }

    $accessToken = $tokenData['access_token'];

    // Send SMS
    $smsData = json_encode([
        "access_token" => $accessToken,
        "source" => $senderId,
        "destination" => $destination,
        "message" => $message
    ]);

    $ch = curl_init($smsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $smsData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $accessToken
    ]);

    $smsResponse = curl_exec($ch);

    if ($smsResponse === false) {
        return json_encode([
            "success" => false,
            "message" => "cURL Error: " . curl_error($ch)
        ]);
    }

    curl_close($ch);

    $smsResult = json_decode($smsResponse, true);

    if (isset($smsResult['success']) && $smsResult['success'] === true) {
        return json_encode([
            "success" => true,
            "message" => "SMS sent successfully!"
        ]);
    } else {
        return json_encode([
            "success" => false,
            "message" => "Failed to send SMS: " . $smsResult['message']
        ]);
    }
}


?>

