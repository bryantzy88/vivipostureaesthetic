<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

class UserAnalysis {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getUserConsumptionRanking($limit = 10) {
        $sql = "SELECT 
                    u.id,
                    u.name,
                    u.avatar,
                    COUNT(po.id) as order_count,
                    SUM(po.amount) as total_spent,
                    MAX(po.created_at) as last_order_time
                FROM users u
                LEFT JOIN payment_orders po ON u.id = po.user_id
                WHERE po.status = 'success'
                GROUP BY u.id
                ORDER BY total_spent DESC
                LIMIT ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getUserConsumptionTrend($userId, $months = 6) {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as order_count,
                    SUM(amount) as total_amount
                FROM payment_orders
                WHERE user_id = ?
                AND status = 'success'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY month
                ORDER BY month";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $userId, $months);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getPopularServices($startDate, $endDate) {
        $sql = "SELECT 
                    s.id,
                    s.name,
                    COUNT(po.id) as order_count,
                    SUM(po.amount) as total_revenue
                FROM services s
                LEFT JOIN payment_orders po ON s.id = po.service_id
                WHERE po.status = 'success'
                AND po.created_at BETWEEN ? AND ?
                GROUP BY s.id
                ORDER BY order_count DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getServiceCategoryStats() {
        $sql = "SELECT 
                    sc.name as category_name,
                    COUNT(po.id) as order_count,
                    SUM(po.amount) as total_revenue
                FROM service_categories sc
                LEFT JOIN services s ON sc.id = s.category_id
                LEFT JOIN payment_orders po ON s.id = po.service_id
                WHERE po.status = 'success'
                GROUP BY sc.id
                ORDER BY total_revenue DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $analysis = new UserAnalysis($conn);
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $response = [
        'code' => 200,
        'data' => [
            'top_consumers' => $analysis->getUserConsumptionRanking(),
            'popular_services' => $analysis->getPopularServices($startDate, $endDate),
            'category_stats' => $analysis->getServiceCategoryStats()
        ]
    ];
    
    if (isset($_GET['user_id'])) {
        $response['data']['user_trend'] = $analysis->getUserConsumptionTrend($_GET['user_id']);
    }
    
    echo json_encode($response);
}
?> 