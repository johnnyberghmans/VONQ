<?php
// Controleer de headers
$headers = getallheaders();

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];


    if ($authHeader !== $expectedPassword) {
        http_response_code(401);
         $error =  "Unauthorized: Invalid password.";
      //  exit;
    }
} else {
    http_response_code(400);
     $error =  "Bad Request: Missing Authorization header.";
   // exit;
}

?>
