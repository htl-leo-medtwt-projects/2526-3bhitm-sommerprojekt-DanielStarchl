<?php

$_db_host = "db_server";
$_db_username = "root";
$_db_password = "rootpassword";
$_db_datenbank = "Playerstats";

$conn = new mysqli($_db_host,
                   $_db_username,
                   $_db_password,
                   $_db_datenbank);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


