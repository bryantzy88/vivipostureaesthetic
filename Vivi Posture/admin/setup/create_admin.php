<?php
require_once '../config/database.php';

$username = 'admin';
$password = 'your_password';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO admin_users (username, password_hash) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password_hash);

if ($stmt->execute()) {
    echo "管理员账户创建成功！";
} else {
    echo "创建失败: " . $conn->error;
}
?> 