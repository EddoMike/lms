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
        error_log("cURL Error (SMS Token): " . curl_error($ch));
        return json_encode([
            "success" => false,
            "message" => "cURL Error: " . curl_error($ch)
        ]);
    }

    curl_close($ch);

    $tokenData = json_decode($tokenResponse, true);

    if (!isset($tokenData['success']) || $tokenData['success'] !== true) {
        error_log("Failed to generate SMS token: " . $tokenData['message']);
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
        error_log("cURL Error (SMS Send): " . curl_error($ch));
        return json_encode([
            "success" => false,
            "message" => "cURL Error: " . curl_error($ch)
        ]);
    }

    curl_close($ch);

    $smsResult = json_decode($smsResponse, true);

    if (isset($smsResult['success']) && $smsResult['success'] === true) {
        error_log("SMS sent successfully to $destination");
        return json_encode([
            "success" => true,
            "message" => "SMS sent successfully!"
        ]);
    } else {
        error_log("Failed to send SMS to $destination: " . $smsResult['message']);
        return json_encode([
            "success" => false,
            "message" => "Failed to send SMS: " . $smsResult['message']
        ]);
    }
}

function sendEmail($to, $subject, $message) {
    // Placeholder for email sending logic
    // In a real application, integrate with an email service like PHPMailer or SendGrid
    error_log("Email would be sent to: $to");
    error_log("Subject: $subject");
    error_log("Message: $message");
    return json_encode([
        "success" => true,
        "message" => "Email sent successfully (placeholder)"
    ]);
    // Example with PHPMailer (uncomment and configure for actual use):
    /*
    require 'vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@example.com';
    $mail->Password = 'your_password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->setFrom('no-reply@laundrysystem.com', 'Laundry Management System');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->isHTML(true);
    if ($mail->send()) {
        error_log("Email sent successfully to $to");
        return json_encode(["success" => true, "message" => "Email sent successfully"]);
    } else {
        error_log("Failed to send email to $to: " . $mail->ErrorInfo);
        return json_encode(["success" => false, "message" => "Failed to send email"]);
    }
    */
}
?>