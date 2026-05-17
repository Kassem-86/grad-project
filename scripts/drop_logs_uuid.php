<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=gradprojectdb', 'root', '');
$pdo->exec('DROP TABLE IF EXISTS `logs_uuid`');
echo "Dropped logs_uuid\n";
