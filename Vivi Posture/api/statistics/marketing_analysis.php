<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

class MarketingAnalysis {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getPromotionEffectiveness($startDate, $endDate) {
        $sql = "SELECT 
                    p.id,
                    p.name as promotion_name,
                    p.type,
                    COUNT(DISTINCT po.user_id) as unique_users,
                    COUNT(po.id) as total_orders,
                    SUM(po.amount) as total_revenue,
                    SUM(po.discount_amount) as total_discount
                FROM promotions p
                LEFT JOIN payment_orders po ON p.id = po.promotion_id
                WHERE po.status = 'success'
                AND po.created_at BETWEEN ? AND ?
                GROUP BY p.id";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getReferralStats() {
        $sql = "SELECT 
                    u.id as referrer_id,
                    u.name as referrer_name,
                    COUNT(r.referred_user_id) as total_referrals,
                    SUM(CASE WHEN po.status = 'success' THEN po.amount ELSE 0 END) as revenue_generated
                FROM users u
                LEFT JOIN referrals r ON u.id = r.referrer_id
                LEFT JOIN payment_orders po ON r.referred_user_id = po.user_id
                GROUP BY u.id
                HAVING total_referrals > 0
                ORDER BY total_referrals DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getChannelPerformance() {
        $sql = "SELECT 
                    acquisition_channel,
                    COUNT(DISTINCT u.id) as total_users,
                    COUNT(po.id) as total_orders,
                    SUM(po.amount) as total_revenue,
                    AVG(po.amount) as avg_order_value
                FROM users u
                LEFT JOIN payment_orders po ON u.id = po.user_id
                WHERE po.status = 'success'
                GROUP BY acquisition_channel";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $analysis = new MarketingAnalysis($conn);
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    $response = [
        'code' => 200,
        'data' => [
            'promotions' => $analysis->getPromotionEffectiveness($startDate, $endDate),
            'referrals' => $analysis->getReferralStats(),
            'channels' => $analysis->getChannelPerformance()
        ]
    ];
    
    echo json_encode($response);
}
?> 