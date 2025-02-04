<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

class TransactionExporter {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function exportTransactions($startDate, $endDate, $format = 'csv') {
        $sql = "SELECT 
                    po.order_no,
                    po.amount,
                    po.status,
                    po.payment_method,
                    po.created_at,
                    u.name as user_name,
                    u.email
                FROM payment_orders po
                LEFT JOIN users u ON po.user_id = u.id
                WHERE po.created_at BETWEEN ? AND ?
                ORDER BY po.created_at DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        switch ($format) {
            case 'csv':
                return $this->generateCSV($transactions);
            case 'excel':
                return $this->generateExcel($transactions);
            default:
                return $this->generateCSV($transactions);
        }
    }
    
    private function generateCSV($data) {
        $filename = 'transactions_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // 添加CSV头部
        fputcsv($output, ['订单号', '金额', '状态', '支付方式', '创建时间', '用户名', '邮箱']);
        
        // 添加数据行
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
    
    private function generateExcel($data) {
        require_once 'vendor/autoload.php'; // 需要安装 PhpSpreadsheet
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 设置表头
        $sheet->setCellValue('A1', '订单号');
        $sheet->setCellValue('B1', '金额');
        $sheet->setCellValue('C1', '状态');
        $sheet->setCellValue('D1', '支付方式');
        $sheet->setCellValue('E1', '创建时间');
        $sheet->setCellValue('F1', '用户名');
        $sheet->setCellValue('G1', '邮箱');
        
        // 添加数据
        $row = 2;
        foreach ($data as $item) {
            $sheet->setCellValue('A'.$row, $item['order_no']);
            $sheet->setCellValue('B'.$row, $item['amount']);
            $sheet->setCellValue('C'.$row, $item['status']);
            $sheet->setCellValue('D'.$row, $item['payment_method']);
            $sheet->setCellValue('E'.$row, $item['created_at']);
            $sheet->setCellValue('F'.$row, $item['user_name']);
            $sheet->setCellValue('G'.$row, $item['email']);
            $row++;
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="transactions_'.date('Y-m-d').'.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
    }
}

// 处理导出请求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $format = $_GET['format'] ?? 'csv';
    
    $exporter = new TransactionExporter($conn);
    $exporter->exportTransactions($startDate, $endDate, $format);
}
?> 