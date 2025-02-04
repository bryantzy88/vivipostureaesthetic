<?php
require_once '../config/database.php';

class Reconciliation {
    private $conn;
    private $date;
    
    public function __construct($conn, $date = null) {
        $this->conn = $conn;
        $this->date = $date ?? date('Y-m-d');
    }
    
    public function runDailyReconciliation() {
        // 获取支付系统订单数据
        $systemOrders = $this->getSystemOrders();
        
        // 获取FPX支付网关订单数据
        $fpx = new FPXPayment();
        $fpxOrders = $fpx->getDailyOrders($this->date);
        
        // 对账处理
        $reconciliationResult = $this->reconcileOrders($systemOrders, $fpxOrders);
        
        // 记录对账结果
        $this->logReconciliation($reconciliationResult);
        
        // 处理异常订单
        $this->handleExceptions($reconciliationResult['exceptions']);
        
        return $reconciliationResult;
    }
    
    private function getSystemOrders() {
        $sql = "SELECT 
                    order_no,
                    amount,
                    status,
                    created_at
                FROM payment_orders
                WHERE DATE(created_at) = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $this->date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    private function reconcileOrders($systemOrders, $fpxOrders) {
        $exceptions = [];
        $matched = [];
        $systemTotal = 0;
        $fpxTotal = 0;
        
        // 创建订单索引
        $systemOrderMap = array_column($systemOrders, null, 'order_no');
        $fpxOrderMap = array_column($fpxOrders, null, 'order_no');
        
        // 检查系统订单
        foreach ($systemOrders as $order) {
            $systemTotal += $order['amount'];
            
            if (!isset($fpxOrderMap[$order['order_no']])) {
                $exceptions[] = [
                    'type' => 'missing_fpx',
                    'order' => $order
                ];
                continue;
            }
            
            $fpxOrder = $fpxOrderMap[$order['order_no']];
            if ($order['amount'] != $fpxOrder['amount']) {
                $exceptions[] = [
                    'type' => 'amount_mismatch',
                    'system_order' => $order,
                    'fpx_order' => $fpxOrder
                ];
                continue;
            }
            
            $matched[] = $order['order_no'];
        }
        
        // 检查FPX订单
        foreach ($fpxOrders as $order) {
            $fpxTotal += $order['amount'];
            
            if (!isset($systemOrderMap[$order['order_no']])) {
                $exceptions[] = [
                    'type' => 'missing_system',
                    'order' => $order
                ];
            }
        }
        
        return [
            'date' => $this->date,
            'system_total' => $systemTotal,
            'fpx_total' => $fpxTotal,
            'matched_count' => count($matched),
            'exception_count' => count($exceptions),
            'exceptions' => $exceptions,
            'matched_orders' => $matched
        ];
    }
    
    private function logReconciliation($result) {
        $sql = "INSERT INTO reconciliation_logs 
                (date, system_total, fpx_total, matched_count, exception_count, details) 
                VALUES (?, ?, ?, ?, ?, ?)";
                
        $stmt = $this->conn->prepare($sql);
        $details = json_encode($result);
        $stmt->bind_param('sddids', 
            $result['date'],
            $result['system_total'],
            $result['fpx_total'],
            $result['matched_count'],
            $result['exception_count'],
            $details
        );
        $stmt->execute();
    }
    
    private function handleExceptions($exceptions) {
        foreach ($exceptions as $exception) {
            // 记录异常
            $sql = "INSERT INTO reconciliation_exceptions 
                    (type, order_no, details, status) 
                    VALUES (?, ?, ?, 'pending')";
                    
            $stmt = $this->conn->prepare($sql);
            $details = json_encode($exception);
            $stmt->bind_param('sss', 
                $exception['type'],
                $exception['order']['order_no'],
                $details
            );
            $stmt->execute();
            
            // 发送通知
            $this->sendExceptionNotification($exception);
        }
    }
    
    private function sendExceptionNotification($exception) {
        // 实现通知逻辑（邮件、短信等）
    }
}

// 执行每日对账
$reconciliation = new Reconciliation($conn);
$result = $reconciliation->runDailyReconciliation();

// 如果有异常，发送汇总报告
if ($result['exception_count'] > 0) {
    // 发送报告给管理员
}
?> 