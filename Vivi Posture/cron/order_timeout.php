<?php
require_once '../config/database.php';

class OrderManager {
    private $conn;
    private $timeoutMinutes = 30; // 支付超时时间
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function handleTimeoutOrders() {
        // 查找超时订单
        $sql = "SELECT * FROM payment_orders 
                WHERE status = 'pending' 
                AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $this->timeoutMinutes);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($order = $result->fetch_assoc()) {
            // 更新订单状态为取消
            $this->cancelOrder($order['order_no'], 'timeout');
            
            // 记录日志
            $this->logOrderCancellation($order['order_no'], 'Payment timeout');
        }
    }
    
    public function cancelOrder($orderNo, $reason) {
        $sql = "UPDATE payment_orders SET status = 'cancelled' WHERE order_no = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $orderNo);
        $stmt->execute();
    }
    
    private function logOrderCancellation($orderNo, $reason) {
        $sql = "INSERT INTO order_logs (order_no, action, reason) VALUES (?, 'cancel', ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $orderNo, $reason);
        $stmt->execute();
    }
}

// 执行超时订单处理
$orderManager = new OrderManager($conn);
$orderManager->handleTimeoutOrders();
?> 