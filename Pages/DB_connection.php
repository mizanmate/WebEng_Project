<?php
// ================================================================
//  DB_connection.php
//  Shared database connection
//  Usage:
//      require_once 'DB_connection.php';   // gives you: $link
// ================================================================

$link = mysqli_connect("localhost", "root", "", "$dbname = "fkcluabeventandmanagement";");

if (!$link) {
    die("Database connection failed: " . mysqli_connect_error());
}
