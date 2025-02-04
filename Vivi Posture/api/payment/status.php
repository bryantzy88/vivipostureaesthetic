<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $orderNo = $_GET['orderNo'];
    
    // 查询订单状态
    $sql = "SELECT po.*, u.email, u.name 
            FROM payment_orders po 
            LEFT JOIN users u ON po.user_id = u.id 
            WHERE po.order_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $orderNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // 如果订单状态是pending，则查询FPX实时状态
        if ($order['status'] === 'pending') {
            $fpx = new FPXPayment();
            $fpxStatus = $fpx->queryStatus($orderNo);
            
            if ($fpxStatus['status'] !== 'pending') {
                // 更新订单状态
                $sql = "UPDATE payment_orders SET status = ? WHERE order_no = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ss', $fpxStatus['status'], $orderNo);
                $stmt->execute();
                
                $order['status'] = $fpxStatus['status'];
            }
        }
        
        $statusMessages = [
            'success' => '支付成功，余额已到账',
            'pending' => '等待支付完成',
            'failed' => '支付失败，请重试',
            'cancelled' => '支付已取消'
        ];
        
        echo json_encode([
            'code' => 200,
            'data' => [
                'status' => $order['status'],
                'amount' => $order['amount'],
                'message' => $statusMessages[$order['status']] ?? '',
                'orderNo' => $order['order_no'],
                'createTime' => $order['created_at']
            ]
        ]);
    } else {
        echo json_encode([
            'code' => 404,
            'message' => '订单不存在'
        ]);
    }
}
?> 