<?php
require_once("myparam.example.inc.php");

$conn = oci_new_connect(MYUSER, MYPASS, MYHOST, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    die("Erreur de connexion à Oracle : " . htmlentities($e['message'], ENT_QUOTES));
}
?>
