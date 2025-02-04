<?php
require_once '../config/database.php';
require_once 'fpx.php';

$fpx = new FPXPayment();
$data = $_POST; // FPX回调数据

if ($fpx->verifyCallback($data)) {
    $orderNo = $data['orderNo'];
    $status = $data['status'];
    
    // 更新订单状态
    $sql = "UPDATE payment_orders SET status = ? WHERE order_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $status, $orderNo);
    
    if ($stmt->execute()) {
        if ($status === 'success') {
            // 查询订单信息
            $sql = "SELECT * FROM payment_orders WHERE order_no = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $orderNo);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            
            // 更新用户余额
            $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('di', $order['amount'], $order['user_id']);
            $stmt->execute();
            
            // 记录充值记录
            $sql = "INSERT INTO recharge_records (user_id, amount, order_no) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ids', $order['user_id'], $order['amount'], $orderNo);
            $stmt->execute();
        }
        
        echo 'OK'; // 返回给FPX的响应
    }
} else {
    http_response_code(400);
    echo 'Invalid signature';
}
?> 