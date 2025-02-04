<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

class FPXPayment {
    private $merchantId = 'YOUR_FPX_MERCHANT_ID';
    private $secretKey = 'YOUR_FPX_SECRET_KEY';
    private $apiEndpoint = 'https://sandbox.fpx.com.my/api/v1'; // 沙箱环境，生产环境需更改

    public function createPayment($orderData) {
        $payload = [
            'merchantId' => $this->merchantId,
            'orderNo' => $orderData['orderNo'],
            'amount' => $orderData['amount'],
            'currency' => 'MYR',
            'description' => $orderData['description'],
            'returnUrl' => 'https://your-domain.com/payment/callback',
            'timestamp' => time(),
            'buyerEmail' => $orderData['email'],
            'buyerName' => $orderData['name'],
            'buyerBankId' => $orderData['bankId']
        ];

        $payload['signature'] = $this->generateSignature($payload);

        return $this->sendRequest('/payment/create', $payload);
    }

    public function verifyCallback($data) {
        $receivedSignature = $data['signature'];
        unset($data['signature']);
        
        $calculatedSignature = $this->generateSignature($data);
        
        return $receivedSignature === $calculatedSignature;
    }

    private function generateSignature($data) {
        ksort($data);
        $signString = '';
        foreach ($data as $key => $value) {
            $signString .= $key . '=' . $value . '&';
        }
        $signString .= $this->secretKey;
        
        return hash('sha256', $signString);
    }

    private function sendRequest($endpoint, $data) {
        $ch = curl_init($this->apiEndpoint . $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}

// 处理支付请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $fpx = new FPXPayment();
    $orderData = [
        'orderNo' => 'ORDER' . time(),
        'amount' => $data['amount'],
        'description' => '充值服务',
        'email' => $data['email'],
        'name' => $data['name'],
        'bankId' => $data['bankId']
    ];

    $result = $fpx->createPayment($orderData);
    
    if ($result['status'] === 'success') {
        // 保存订单信息
        $sql = "INSERT INTO payment_orders (order_no, amount, user_id, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sdi', $orderData['orderNo'], $orderData['amount'], $_SESSION['user_id']);
        $stmt->execute();

        echo json_encode([
            'code' => 200,
            'data' => [
                'paymentUrl' => $result['paymentUrl'],
                'orderNo' => $orderData['orderNo']
            ]
        ]);
    } else {
        echo json_encode([
            'code' => 500,
            'message' => '创建支付订单失败'
        ]);
    }
}
?> 