<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

class PaymentStatistics {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getDailyStats($startDate, $endDate) {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_orders,
                    SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as total_amount
                FROM payment_orders 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getPaymentMethodStats() {
        $sql = "SELECT 
                    payment_method,
                    COUNT(*) as total_orders,
                    SUM(amount) as total_amount
                FROM payment_orders 
                WHERE status = 'success'
                GROUP BY payment_method";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getSuccessRate() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                    (SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as success_rate
                FROM payment_orders";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

// 处理统计请求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stats = new PaymentStatistics($conn);
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $response = [
        'code' => 200,
        'data' => [
            'daily_stats' => $stats->getDailyStats($startDate, $endDate),
            'payment_method_stats' => $stats->getPaymentMethodStats(),
            'success_rate' => $stats->getSuccessRate()
        ]
    ];
    
    echo json_encode($response);
}
?> 