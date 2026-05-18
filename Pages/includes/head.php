<?php
// ================================================================
//  includes/head.php
//  Shared <head> block — include inside every page's <head> tag.
//
//  Usage (in your PHP page):
//      $pageTitle = 'My Page Title';   // set this before including
//      <head><?php include 'includes/head.php'; ? ></head>
// ================================================================
?>
<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($pageTitle ?? 'FK CEM System') ?></title>

<link rel="stylesheet" href="../CSS/style.css">
