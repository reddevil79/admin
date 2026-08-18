<?php
require_once("DBConnection.php");

$product_id = 0;
$product_code = '';
$category_id = '';
$name = '';
$price = '';
$image = '';
$alert_restock = 0;
$stock = 0;
$description = '';
$status = 1;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM `product_list` WHERE product_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $product_code  = $row['product_code'] ?? '';
            $category_id   = $row['category_id'] ?? '';
            $name          = $row['name'] ?? '';
            $price         = $row['price'] ?? '';
            $image         = $row['image'] ?? '';
            $alert_restock = $row['alert_restock'] ?? 0;
            $stock         = $row['stock'] ?? 0;
            $description   = $row['description'] ?? '';
            $status        = $row['status'] ?? 1;
        }
        $stmt->close();
    }
}
?>

<div class="container-fluid py-2">
    <form action="" id="product-form" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $product_id > 0 ? $product_id : ''; ?>">
        
        <div class="row g-3">
            <!-- Left Column -->
            <div class="col-md-6">
                <div class="form-floating mb-3">
                    <input type="text" name="product_code" autofocus id="product_code" required class="form-control rounded-3" placeholder="Code" value="<?php echo htmlspecialchars($product_code); ?>">
                    <label for="product_code">Product Code <span class="text-danger">*</span></label>
                </div>

                <div class="mb-3">
                    <label for="category_id" class="form-label small fw-bold text-muted">Category <span class="text-danger">*</span></label>
                    <select name="category_id" id="category_id" class="form-select rounded-3 select2" required>
                        <option value="" <?php echo empty($category_id) ? 'selected' : ''; ?> disabled>Please Select Category</option>
                        <?php
                        $cat_stmt = $conn->prepare("SELECT * FROM category_list WHERE `status` = 1 AND `delete_flag` = 0 ORDER BY `name` ASC");
                        if ($cat_stmt) {
                            $cat_stmt->execute();
                            $cat_qry = $cat_stmt->get_result();
                            while ($row = $cat_qry->fetch_assoc()):
                        ?>
                            <option value="<?php echo $row['category_id']; ?>" <?php echo ($category_id == $row['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php 
                            endwhile;
                            $cat_stmt->close();
                        }
                        ?>
                    </select>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" name="name" id="name" required class="form-control rounded-3" placeholder="Name" value="<?php echo htmlspecialchars($name); ?>">
                    <label for="name">Product Name <span class="text-danger">*</span></label>
                </div>

                <div class="form-floating mb-3">
                    <input type="number" step="any" min="0" name="price" id="price" required class="form-control rounded-3 text-end" placeholder="Price" value="<?php echo htmlspecialchars($price); ?>">
                    <label for="price">Price (Rs.) <span class="text-danger">*</span></label>
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label small fw-bold text-muted">Product Image</label>
                    <?php 
                    $filename = basename($image);
                    $paths_to_check = [
                        $image,
                        'images/products/' . $filename,
                        'admin/images/products/' . $filename
                    ];
                    
                    $img_path = '';
                    foreach ($paths_to_check as $p) {
                        if (!empty($image) && file_exists($p)) {
                            $img_path = $p;
                            break;
                        }
                    }

                    if (!empty($img_path)): 
                    ?>
                        <div class="mb-2 d-flex align-items-center gap-2 p-2 border rounded-3 bg-light">
                            <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Product Image" class="img-thumbnail rounded-2 shadow-sm" style="height: 50px; width: 50px; object-fit: cover;">
                            <span class="text-muted small">Current image loaded</span>
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($image); ?>">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" id="image" class="form-control rounded-3" accept="image/*" <?php echo ($product_id <= 0) ? 'required' : ''; ?>>
                    <?php if ($product_id > 0): ?>
                        <small class="text-muted fs-7">Leave blank to retain current image.</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6">
                <div class="form-floating mb-3">
                    <input type="number" name="stock" id="stock" required class="form-control rounded-3 text-end" placeholder="Total Stock" value="<?php echo htmlspecialchars($stock); ?>">
                    <label for="stock">Total Stock Quantity <span class="text-danger">*</span></label>
                </div>

                <div class="form-floating mb-3">
                    <input type="number" name="alert_restock" id="alert_restock" required class="form-control rounded-3 text-end" placeholder="Restock Alert" value="<?php echo htmlspecialchars($alert_restock); ?>">
                    <label for="alert_restock">QTY Alert for Restock <span class="text-danger">*</span></label>
                </div>

                <div class="form-floating mb-3">
                    <textarea name="description" id="description" style="height: 105px;" class="form-control rounded-3" placeholder="Description" required><?php echo htmlspecialchars($description); ?></textarea>
                    <label for="description">Description <span class="text-danger">*</span></label>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label small fw-bold text-muted">Status <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select rounded-3" required>
                        <option value="1" <?php echo ($status == 1) ? 'selected' : ''; ?>>Popular</option>
                        <option value="0" <?php echo ($status == 0) ? 'selected' : ''; ?>>Unpopular</option>
                    </select>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    $(function(){
        if ($.fn.select2) {
            $('.select2').select2({
                dropdownParent: $('#uni_modal'),
                width: '100%'
            });
        }

        $('#product-form').submit(function(e){
            e.preventDefault();
            $('.pop_msg').remove();
            var _this = $(this);
            var _el = $('<div>').addClass('pop_msg alert alert-dismissible fade show mb-3');
            
            $('#uni_modal button').attr('disabled', true);
            $('#uni_modal button[type="submit"]').html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');
            
            $.ajax({
                url: './Actions.php?a=save_product',
                data: new FormData(this),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                dataType: 'json',
                error: function(xhr, status, error){
                    console.log("Error details:", xhr, status, error);
                    _el.addClass('alert-danger').html("An unexpected database error occurred while saving.");
                    _this.prepend(_el);
                    _el.show('slow');
                    $('#uni_modal button').attr('disabled', false);
                    $('#uni_modal button[type="submit"]').text('Save');
                },
                success: function(resp){
                    if(resp && resp.status === 'success'){
                        _el.addClass('alert-success').html('<i class="fa fa-check-circle me-2"></i>' + (resp.msg || 'Product saved successfully.'));
                        _this.prepend(_el);
                        _el.show('slow');
                        setTimeout(function(){
                            location.reload();
                        }, 1000);
                    } else {
                        _el.addClass('alert-danger').html('<i class="fa fa-exclamation-triangle me-2"></i>' + ((resp && resp.msg) ? resp.msg : 'Save failed.'));
                        _this.prepend(_el);
                        _el.show('slow');
                        $('#uni_modal button').attr('disabled', false);
                        $('#uni_modal button[type="submit"]').text('Save');
                    }
                }
            });
        });
    });
</script>