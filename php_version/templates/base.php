<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Python教學平台'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Prism.js for code highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/static/css/style.css">
    
    <?php if (isset($extraHead)): ?>
        <?php echo $extraHead; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-code me-2"></i>Python教學平台
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage ?? '') === 'home' ? 'active' : ''; ?>" href="/">
                            <i class="fas fa-home me-1"></i>首頁
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage ?? '') === 'lessons' ? 'active' : ''; ?>" href="/lessons">
                            <i class="fas fa-book me-1"></i>課程
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage ?? '') === 'ai-tutor' ? 'active' : ''; ?>" href="/ai-tutor">
                            <i class="fas fa-robot me-1"></i>AI助教
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage ?? '') === 'practice' ? 'active' : ''; ?>" href="/practice">
                            <i class="fas fa-code me-1"></i>練習
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <?php echo $content ?? ''; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-code me-2"></i>Python教學平台</h5>
                    <p class="mb-0">專業的Python學習環境，結合AI助教提供個人化教學體驗</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6>聯絡資訊</h6>
                    <p class="mb-0">
                        <i class="fas fa-envelope me-2"></i>support@python-teaching.com<br>
                        <i class="fas fa-phone me-2"></i>0900-123-456
                    </p>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center">
                <small>&copy; 2024 Python教學平台. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <!-- Back to top button -->
    <button id="backToTop" class="btn btn-primary back-to-top" title="回到頂部">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Prism.js for code highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/static/js/main.js"></script>
    
    <?php if (isset($extraJS)): ?>
        <?php echo $extraJS; ?>
    <?php endif; ?>
</body>
</html> 