<?php
// mysql:host=-;dbname=-', '-', '-'
$dbhost = "-";
$dbuser = "-";
$dbpass = "-";
$dbname = "-";
$dbport = "-";

if(!$con = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname))
{

    die("failed to connect");
}