<?php
session_start();
require __DIR__ . "/config/db.php";
require __DIR__ . "/config/log.php";

logActivity('auth', 'logout', 'User melakukan logout');

session_unset();
session_destroy();

header("Location: /mavis/login.php");
exit;
