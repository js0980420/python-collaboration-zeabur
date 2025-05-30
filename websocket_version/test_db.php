<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;dbname=python_collaboration', 'root', '');
    echo "✅ 數據庫連接成功" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ 數據庫連接失敗: " . $e->getMessage() . PHP_EOL;
}
?> 