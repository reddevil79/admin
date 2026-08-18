<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ./login.php");
    exit;
}

require_once('DBConnection.php');

// Sanitize page navigation parameter to prevent directory traversal attacks
$page = isset($_GET['page']) ? trim($_GET['page']) : 'home';
$allowed_pages = [
    'home',
    'products',
    'sales',
    'sales_report',
    'users',
    'maintenance',
    'manage_account'
];

if (!in_array($page, $allowed_pages, true)) {
    $page = 'home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $page))); ?> | Bakery Management System</title>
    
    <link rel="stylesheet" href="./Font-Awesome-master/css/all.min.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./select2/css/select2.min.css">
    <link rel="stylesheet" href="./DataTables/datatables.min.css">
    <link rel="stylesheet" href="css/style.css">

    <script src="./js/jquery-3.6.0.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./DataTables/datatables.min.js"></script>
    <script src="./select2/js/select2.full.min.js"></script>
    <script src="./Font-Awesome-master/js/all.min.js"></script>
    <script src="./js/script.js"></script>
    
    <style>
        :root {
            --bs-success-rgb: 71, 222, 152 !important;
        }
        html, body {
            height: 100%;
            width: 100%;
        }
        .thumbnail-img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    background-color: #f8f9fa; /* Fallback background if image is loading */
}
        @media screen {
            body {
                background-image: url('images/bg.jpg') !important; 
                background-size: cover;
                background-repeat: no-repeat;
                background-position: center center;
                backdrop-filter: brightness(0.7);
            }
        }
        main {
            height: 100%;
            display: flex;
            flex-flow: column;
        }
        #page-container {
            flex: 1 1 auto; 
            overflow: auto;
        }
        #topNavBar {
            flex: 0 1 auto; 
        }
        .thumbnail-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
            margin: 2px;
        }
        .truncate-1 {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
        }
        .truncate-3 {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .modal-dialog.large {
            width: 80% !important;
            max-width: unset;
        }
        .modal-dialog.mid-large {
            width: 50% !important;
            max-width: unset;
        }
        @media (max-width: 720px) {
            .modal-dialog.large, .modal-dialog.mid-large {
                width: 100% !important;
                max-width: unset;
            }  
            .display-select-image {
                width: 40px;
                height: 40px;
                margin: 2px;
            }
            img.display-image {
                height: 30vh;
                object-fit: contain;
            }
        }
        @media (max-width: 576px) {
            .thumbnail-img {
                width: 40px;
                height: 40px;
            }
        }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #888; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
        
        .img-del-btn {
            right: 2px;
            top: -3px;
        }
        .img-del-btn > .btn {
            font-size: 10px;
            padding: 0px 2px !important;
        }
    </style>
</head>
<body>
    <main>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark bg-gradient shadow-sm" id="topNavBar">
            <div class="container">
                <a class="navbar-brand fw-bold" href="./"><i class="fa fa-cookie-bite me-2 text-warning"></i>Donut Pasal</a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo ($page == 'home') ? 'active fw-semibold text-warning' : ''; ?>" href="./">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo ($page == 'products') ? 'active fw-semibold text-warning' : ''; ?>" href="./?page=products">Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo ($page == 'sales') ? 'active fw-semibold text-warning' : ''; ?>" href="./?page=sales">Transactions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo ($page == 'sales_report') ? 'active fw-semibold text-warning' : ''; ?>" href="./?page=sales_report">Sales</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo ($page == 'users') ? 'active fw-semibold text-warning' : ''; ?>" href="./?page=users">Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 <?php echo ($page == 'maintenance') ? 'active fw-semibold text-warning' : ''; ?>" href="./?page=maintenance">Category</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle bg-transparent border-0 rounded-pill px-3 py-2" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-user-circle me-1 text-warning"></i> <?php echo isset($_SESSION['username']) ? "Hello, " . htmlspecialchars($_SESSION['username']) : "Please login"; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2" aria-labelledby="dropdownMenuButton1">
                                <li><a class="dropdown-item py-2 px-3" href="./?page=manage_account"><i class="fa fa-user-cog me-2 text-muted"></i>Manage Account</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><a class="dropdown-item py-2 px-3 text-danger" href="./Actions.php?a=logout"><i class="fa fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container py-4" id="page-container">
            <?php if (isset($_SESSION['flashdata'])): ?>
                <div class="dynamic_alert alert alert-<?php echo htmlspecialchars($_SESSION['flashdata']['type']); ?> rounded-3 shadow-sm alert-dismissible fade show" role="alert">
                    <i class="fa fa-info-circle me-2"></i><?php echo htmlspecialchars($_SESSION['flashdata']['msg']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flashdata']); ?>
            <?php endif; ?>

            <?php
                $target_file = $page . '.php';
                if (file_exists($target_file)) {
                    include $target_file;
                } else {
                    echo "<div class='alert alert-danger rounded-3 shadow-sm'>Page not found.</div>";
                }
            ?>
        </div>
    </main>

    <!-- Global Modals with advanced Bootstrap styling -->
    <div class="modal fade" id="uni_modal" role="dialog" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-md modal-dialog-centered rounded-4" role="document">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-light py-3 px-4 border-bottom-0">
                    <h5 class="modal-title fw-bold text-dark"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3"></div>
                <div class="modal-footer bg-light py-3 px-4 border-top-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 border shadow-sm" data-bs-dismiss="modal"><i class="fa fa-times me-1"></i>Close</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="submit" onclick="$('#uni_modal form').submit()"><i class="fa fa-save me-1"></i>Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uni_modal_secondary" role="dialog" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-md modal-dialog-centered rounded-4" role="document">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-light py-3 px-4 border-bottom-0">
                    <h5 class="modal-title fw-bold text-dark"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3"></div>
                <div class="modal-footer bg-light py-3 px-4 border-top-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 border shadow-sm" data-bs-dismiss="modal"><i class="fa fa-times me-1"></i>Close</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" id="submit" onclick="$('#uni_modal_secondary form').submit()"><i class="fa fa-save me-1"></i>Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm_modal" role="dialog" data-bs-backdrop="static">
        <div class="modal-dialog modal-md modal-dialog-centered rounded-4" role="document">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-light py-3 px-4 border-bottom-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="fa fa-exclamation-triangle text-warning me-2"></i>Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div id="delete_content" class="fs-6 text-secondary"></div>
                </div>
                <div class="modal-footer bg-light py-3 px-4 border-top-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 border shadow-sm" data-bs-dismiss="modal"><i class="fa fa-times me-1"></i>Close</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm" id="confirm"><i class="fa fa-check me-1"></i>Continue</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>