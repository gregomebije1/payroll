<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}
error_reporting(E_ALL);

require_once "util.inc";


my_redirect('di.php?type=Allowances','');
?>
