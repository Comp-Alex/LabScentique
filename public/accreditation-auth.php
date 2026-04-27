<?php
declare(strict_types=1);
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Just load and display the static HTML - all content is now in accreditation.html
// This file only handles session/auth for future extensions
readfile(__DIR__ . '/accreditation.html');
