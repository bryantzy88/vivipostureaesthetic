<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

class RefundManager {
    private $conn;
    private $fpx;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->fpx = new FPXPayment();
    }
    
    public function processRefund($orderNo, $reason) {
        // 查询订单信息
        $sql = "SELECT * FROM payment_orders WHERE order_no = ? AND status = 'success'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $orderNo);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            return ['success' => false, 'message' => '订单不存在或状态不允许退款'];
        }
        
        // 创建退款请求
        $refundData = [
            'orderNo' => $order['order_no'],
            'refundNo' => 'REF' . time(),
            'amount' => $order['amount'],
            'reason' => $reason
        ];
        
        $result = $this->fpx->createRefund($refundData);
        
        if ($result['status'] === 'success') {
            // 更新订单状态
            $this->updateOrderStatus($orderNo, 'refunded');
            
            // 扣减用户余额
            $this->deductUserBalance($order['user_id'], $order['amount']);
            
            // 记录退款记录
            $this->logRefund($refundData);
            
            return ['success' => true, 'message' => '退款申请已提交'];
        }
        
        return ['success' => false, 'message' => '退款失败：' . $result['message']];
    }
    
    private function updateOrderStatus($orderNo, $status) {
        $sql = "UPDATE payment_orders SET status = ? WHERE order_no = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $status, $orderNo);
        $stmt->execute();
    }
    
    private function deductUserBalance($userId, $amount) {
        $sql = "UPDATE users SET balance = balance - ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('di', $amount, $userId);
        $stmt->execute();
    }
    
    private function logRefund($refundData) {
        $sql = "INSERT INTO refund_records (order_no, refund_no, amount, reason) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssds', 
            $refundData['orderNo'],
            $refundData['refundNo'],
            $refundData['amount'],
            $refundData['reason']
        );
        $stmt->execute();
    }
}

// 处理退款请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $refundManager = new RefundManager($conn);
    $result = $refundManager->processRefund($data['orderNo'], $data['reason']);
    
    echo json_encode($result);
}
?> 