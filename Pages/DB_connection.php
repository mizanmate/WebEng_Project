<?php
// ================================================================
//  DB_connection.php
//  Shared database connection
//  Usage:
//      require_once 'DB_connection.php';   // gives you: $link
// ================================================================

$link = mysqli_connect("localhost", "root", "", "fkclubandeventmanagement");

if (!$link) {
    die("Database connection failed: " . mysqli_connect_error());
}
