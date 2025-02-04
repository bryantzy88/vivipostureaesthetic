-- 退款记录表
CREATE TABLE refund_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_no VARCHAR(50) NOT NULL,
    refund_no VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_no) REFERENCES payment_orders(order_no)
);

-- 订单日志表
CREATE TABLE order_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_no VARCHAR(50) NOT NULL,
    action VARCHAR(20) NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_no) REFERENCES payment_orders(order_no)
);

-- 添加索引
CREATE INDEX idx_refund_no ON refund_records(refund_no);
CREATE INDEX idx_order_logs_order_no ON order_logs(order_no); 