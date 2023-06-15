<?php
//***************************************************
//RSerrorMailing.php
//***************************************************
//Description:
//  sends email when necessary to handle a possible
//  error when something triggers
//***************************************************

function sendEmail($subject, $message) {

    // Mailing data
    global $SMTPServer;
    global $mailUser;
    global $mailPassword;
    global $mailRecipient;

    $emailContent = "Subject: $subject\r\n\r\n$message";

    // Create a temporary file to store the email message
    $emailFile = fopen("php://temp", 'w+');
    fwrite($emailFile, $emailContent);
    rewind($emailFile);
    $fstat = fstat($emailFile);
    $size = $fstat['size'];

    // Initialize cURL
    $ch = curl_init($SMTPServer);

    // Set the email headers and content
    curl_setopt($ch, CURLOPT_MAIL_FROM, "<" .  $mailUser . ">");
    curl_setopt($ch, CURLOPT_MAIL_RCPT, array("<" . $mailRecipient . ">"));

    // Set the username and password for authentication
    curl_setopt($ch, CURLOPT_USERNAME, $mailUser);
    curl_setopt($ch, CURLOPT_PASSWORD, $mailPassword);

    // Auth method
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // Specify that we want to upload a file (the email content)
    curl_setopt($ch, CURLOPT_PUT, 1);
    curl_setopt($ch, CURLOPT_INFILE, $emailFile);
    curl_setopt($ch, CURLOPT_INFILESIZE, $size);

    // To analyze the output
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $data = curl_exec($ch);
    
    if ($data === false) {
        error_log('cURL error: ' . curl_error($ch));
    }
    fclose($emailFile);
}
