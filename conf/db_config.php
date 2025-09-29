<?php
$host = "infodb.ansan.ac.kr";
$username = "i2251014";
$password = "junho125!";
$dbname = "db2251014_webDB";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}
?>