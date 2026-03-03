<?php
//pour établir la connexion avec la base de donnée 
$servername = 'localhost';
$username = 'root';
$password = '';
$database = 'challenge_hub';

$conn = mysqli_connect($servername, $username, $password, $database);
if (!$conn) {
    die('Erreur de connexion : ' . mysqli_connect_error());
}
echo 'connexion reussie';
?>