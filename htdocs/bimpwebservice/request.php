<?php

$response = array(
//    'headers' => getallheaders(),
    'server' => $_SERVER,
    'post'   => $_POST,
    'get'    => $_GET
);

header("Content-Type: application/json");
echo json_encode($response, JSON_UNESCAPED_UNICODE);

