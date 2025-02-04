<?php
// 根据您的环境修改这些值
$host = 'localhost';
$dbname = 'beauty_center';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "连接失败: " . $e->getMessage();
}
?> 