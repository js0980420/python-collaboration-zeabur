USE python_collaboration;

CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id VARCHAR(100) NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_created (room_id, created_at)
); 