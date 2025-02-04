<?php
require_once '../auth/auth_check.php';
require_once '../config/database.php';

checkAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // 获取预约列表
        $sql = "SELECT * FROM appointments ORDER BY appointment_time DESC";
        $result = $conn->query($sql);
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $appointments]);
        break;
        
    case 'POST':
        // 更新预约状态
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $conn->real_escape_string($data['id']);
        $status = $conn->real_escape_string($data['status']);
        
        $sql = "UPDATE appointments SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失败']);
        }
        break;
}
?> 