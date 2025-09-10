<?php

declare(strict_types=1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Retrieve service_id from POST.
$serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;

header("Content-Type: text/plain");
echo "Service ID: " . $serviceId;
