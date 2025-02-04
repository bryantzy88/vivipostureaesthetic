-- 对账记录表
CREATE TABLE reconciliation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    system_total DECIMAL(10,2) NOT NULL,
    fpx_total DECIMAL(10,2) NOT NULL,
    matched_count INT NOT NULL,
    exception_count INT NOT NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 对账异常表
CREATE TABLE reconciliation_exceptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(20) NOT NULL,
    order_no VARCHAR(50) NOT NULL,
    details JSON,
    status ENUM('pending', 'resolved', 'ignored') DEFAULT 'pending',
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolved_by INT,
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- 营销活动效果表
CREATE TABLE marketing_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    budget DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    target_audience JSON,
    metrics JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 用户行为分析表
CREATE TABLE user_behaviors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 添加索引
CREATE INDEX idx_reconciliation_date ON reconciliation_logs(date);
CREATE INDEX idx_exception_status ON reconciliation_exceptions(status);
CREATE INDEX idx_campaign_date ON marketing_campaigns(start_date, end_date);
CREATE INDEX idx_user_behavior ON user_behaviors(user_id, event_type); 