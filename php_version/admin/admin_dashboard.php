<?php
// 🎛️ 管理後台主頁面
session_start();
require_once '../config/database.php';

// 檢查管理員權限
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

function isAdmin($userId) {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT role FROM users WHERE id = ? AND role = 'admin'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetch() !== false;
}

// 獲取統計數據
function getDashboardStats() {
    $db = Database::getInstance()->getConnection();
    
    $stats = [];
    
    // 用戶統計
    $sql = "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_week,
                COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as active_users_today
            FROM users";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 學習統計
    $sql = "SELECT 
                COUNT(*) as total_executions,
                COUNT(CASE WHEN executed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as executions_today,
                AVG(experience_gained) as avg_exp_per_execution
            FROM code_executions";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $stats['learning'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 錯誤統計
    $sql = "SELECT 
                COUNT(*) as total_errors,
                COUNT(CASE WHEN occurred_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as errors_today,
                AVG(time_to_fix_seconds) as avg_fix_time
            FROM code_error_logs";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $stats['errors'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 協作統計
    $sql = "SELECT 
                COUNT(*) as total_rooms,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rooms,
                AVG(current_members) as avg_members_per_room
            FROM collaboration_rooms";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $stats['collaboration'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $stats;
}

$stats = getDashboardStats();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理後台 - Python教學平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../static/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- 側邊導航 -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-cogs"></i> 管理後台</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard">
                    <i class="fas fa-tachometer-alt"></i> 儀表板
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#users">
                    <i class="fas fa-users"></i> 用戶管理
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#content">
                    <i class="fas fa-book"></i> 內容管理
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#analytics">
                    <i class="fas fa-chart-bar"></i> 學習分析
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#collaboration">
                    <i class="fas fa-handshake"></i> 協作管理
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#system">
                    <i class="fas fa-server"></i> 系統設定
                </a>
            </li>
        </ul>
    </nav>

    <!-- 主要內容區域 -->
    <main class="main-content">
        <!-- 頂部導航 -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <div class="container-fluid">
                <h5 class="mb-0">Python教學平台管理系統</h5>
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> 管理員
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../index.php">返回前台</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../api/auth_api.php?action=logout">登出</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- 儀表板內容 -->
        <div id="dashboard" class="content-section">
            <div class="container-fluid py-4">
                <h2 class="mb-4">系統概覽</h2>
                
                <!-- 統計卡片 -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            總用戶數
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= number_format($stats['users']['total_users']) ?>
                                        </div>
                                        <div class="text-xs text-success">
                                            <i class="fas fa-arrow-up"></i>
                                            本週新增 <?= $stats['users']['new_users_week'] ?> 人
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            今日活躍用戶
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= number_format($stats['users']['active_users_today']) ?>
                                        </div>
                                        <div class="text-xs text-info">
                                            <i class="fas fa-clock"></i>
                                            24小時內登入
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            程式碼執行次數
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= number_format($stats['learning']['total_executions']) ?>
                                        </div>
                                        <div class="text-xs text-success">
                                            <i class="fas fa-play"></i>
                                            今日 <?= $stats['learning']['executions_today'] ?> 次
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-code fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            協作房間
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= number_format($stats['collaboration']['total_rooms']) ?>
                                        </div>
                                        <div class="text-xs text-info">
                                            <i class="fas fa-handshake"></i>
                                            活躍中 <?= $stats['collaboration']['active_rooms'] ?> 個
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-handshake fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 圖表區域 -->
                <div class="row">
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">學習活動趨勢</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="learningTrendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">錯誤類型分布</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="errorTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 最近活動 -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">最近註冊用戶</h6>
                            </div>
                            <div class="card-body">
                                <div id="recentUsers"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">系統警告</h6>
                            </div>
                            <div class="card-body">
                                <div id="systemAlerts"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 用戶管理 -->
        <div id="users" class="content-section" style="display: none;">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>用戶管理</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus"></i> 新增用戶
                    </button>
                </div>

                <!-- 搜索和篩選 -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="userSearch" placeholder="搜索用戶名或郵箱">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="userRoleFilter">
                                    <option value="">所有角色</option>
                                    <option value="student">學生</option>
                                    <option value="teacher">教師</option>
                                    <option value="admin">管理員</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="userStatusFilter">
                                    <option value="">所有狀態</option>
                                    <option value="active">活躍</option>
                                    <option value="inactive">非活躍</option>
                                    <option value="banned">已禁用</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary" onclick="searchUsers()">
                                    <i class="fas fa-search"></i> 搜索
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 用戶列表 -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>用戶名</th>
                                        <th>郵箱</th>
                                        <th>角色</th>
                                        <th>等級</th>
                                        <th>經驗值</th>
                                        <th>註冊時間</th>
                                        <th>最後登入</th>
                                        <th>狀態</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <!-- 動態載入 -->
                                </tbody>
                            </table>
                        </div>
                        <nav>
                            <ul class="pagination justify-content-center" id="usersPagination">
                                <!-- 動態載入 -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- 其他內容區域... -->
    </main>

    <!-- 新增用戶模態框 -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">新增用戶</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label class="form-label">用戶名</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">郵箱</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">密碼</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">顯示名稱</label>
                            <input type="text" class="form-control" name="display_name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">角色</label>
                            <select class="form-select" name="role">
                                <option value="student">學生</option>
                                <option value="teacher">教師</option>
                                <option value="admin">管理員</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">新增</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../static/js/admin.js"></script>
</body>
</html> 