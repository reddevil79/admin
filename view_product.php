<?php
require_once("DBConnection.php");

$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;

if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT p.*, COALESCE(c.name, 'Unassigned') as cname 
                            FROM `product_list` p 
                            LEFT JOIN `category_list` c ON p.category_id = c.category_id 
                            WHERE p.product_id = ? AND p.delete_flag = 0");
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $product = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

if (!$product):
?>
    <div class="container-fluid py-3">
        <div class="alert alert-danger mb-0 rounded-3 shadow-sm">
            <i class="fa fa-exclamation-triangle me-2"></i>Product record not found or invalid request ID.
        </div>
        <div class="w-100 d-flex justify-content-end mt-3">
            <button class="btn btn-sm btn-secondary rounded-pill px-4 shadow-sm" type="button" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
<?php
    exit;
endif;

// Multi-path image lookup matching product.php and manage_product.php
$filename = basename($product['image'] ?? '');
$paths_to_check = [
    $product['image'] ?? '',
    'images/products/' . $filename,
    'admin/images/products/' . $filename,
    'uploads/products/' . $filename,
    'admin/uploads/products/' . $filename
];

$img_path = 'images/no-image.png';
foreach ($paths_to_check as $p) {
    if (!empty($product['image']) && file_exists($p)) {
        $img_path = $p;
        break;
    }
}

$stock = isset($product['stock']) ? (float)$product['stock'] : 0;
$alert = isset($product['alert_restock']) ? (float)$product['alert_restock'] : 0;
?>
<style>
    #uni_modal .modal-footer {
        display: none !important;
    }
</style>

<div class="container-fluid py-2">
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-3">
        <div class="card-body p-4">
            <!-- Product Image Display -->
            <div class="text-center mb-4">
                <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>" class="img-thumbnail rounded-4 shadow-sm" style="max-height: 160px; width: 160px; object-fit: cover;">
            </div>

            <!-- Details List -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="small text-muted fw-semibold mb-1">Product Code</div>
                        <div class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($product['product_code'] ?? 'N/A'); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="small text-muted fw-semibold mb-1">Category</div>
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($product['cname'] ?? 'Unassigned'); ?></div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="small text-muted fw-semibold mb-1">Product Name</div>
                        <div class="fw-bold fs-6 text-dark"><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="small text-muted fw-semibold mb-1">Description</div>
                        <div class="text-secondary small"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description provided.')); ?></div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 border text-center h-100">
                        <div class="small text-muted fw-semibold mb-1">Price</div>
                        <div class="fw-bold text-success fs-5">Rs. <?php echo number_format((float)($product['price'] ?? 0), 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 border text-center h-100">
                        <div class="small text-muted fw-semibold mb-1">Current Stock</div>
                        <div class="fw-bold fs-5 <?php echo $stock <= $alert ? 'text-danger' : 'text-dark'; ?>">
                            <?php echo number_format($stock); ?> <span class="fs-7 fw-normal text-muted">units</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 border text-center h-100">
                        <div class="small text-muted fw-semibold mb-1">Restock Alert Level</div>
                        <div class="fw-bold text-warning fs-5"><?php echo number_format($alert); ?> <span class="fs-7 fw-normal text-muted">units</span></div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                        <span class="small text-muted fw-semibold">Popularity Status:</span>
                        <span>
                            <?php if ((int)($product['status'] ?? 0) === 1): ?>
                                <span class="badge rounded-pill bg-primary bg-gradient px-3 py-2"><i class="fa fa-star me-1"></i> Popular</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-secondary bg-gradient px-3 py-2"><i class="fa fa-circle-o me-1"></i> Unpopular</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="w-100 d-flex justify-content-end pt-2 border-top">
        <button class="btn btn-sm btn-secondary px-4 rounded-pill shadow-sm" type="button" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
    </div>
</div>