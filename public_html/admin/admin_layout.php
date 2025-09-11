<?php
require_once __DIR__ . '/../includes/header.php';
require_admin();
?>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="admin-layout-container">
    <aside class="admin-sidebar">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
    </aside>
    
    <div class="admin-main-content">
        <?php 
        // This is where the page content will be included
        if (isset($page_content)) {
            echo $page_content;
        }
        ?>
    </div>
</div>

<style>
.admin-layout-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
    margin-top: 20px;
    min-height: calc(100vh - 120px);
}

.admin-sidebar {
    /* Sidebar styles are in admin_nav.php */
}

.admin-main-content {
    /* Main content area */
}

/* Responsive design */
@media (max-width: 1024px) {
    .admin-layout-container {
        grid-template-columns: 1fr;
    }
    
    .admin-sidebar {
        position: relative;
        top: 0;
        max-height: none;
        margin-bottom: 20px;
    }
}

/* Admin-specific global styles */
.admin-layout-container .btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.admin-layout-container .btn-primary {
    background: #667eea;
    color: white;
}

.admin-layout-container .btn-primary:hover {
    background: #5a67d8;
    transform: translateY(-1px);
}

.admin-layout-container .btn-secondary {
    background: #6c757d;
    color: white;
}

.admin-layout-container .btn-secondary:hover {
    background: #5a6268;
}

.admin-layout-container .btn-danger {
    background: #e74c3c;
    color: white;
}

.admin-layout-container .btn-danger:hover {
    background: #c0392b;
}

/* Print styles */
@media print {
    .admin-sidebar,
    .page-header .header-actions,
    .header-controls,
    .export-actions {
        display: none !important;
    }
    
    .admin-layout-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>