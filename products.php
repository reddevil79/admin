<?php 
require_once('DBConnection.php');
?>

<div class="content py-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-0"><span class="fa fa-boxes me-2 text-primary"></span>Product List</h3>
                <p class="text-muted small mb-0">Manage your bakery items, pricing, stock quantities, and availability.</p>
            </div>
            <div class="card-tools">
                <button class="btn btn-primary bg-gradient btn-sm px-3 py-2 rounded-pill shadow-sm" type="button" id="create_new">
                    <i class="fa fa-plus me-1"></i> Add New Product
                </button>
            </div>
        </div>
        
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle border" id="product-table">
                    <thead class="table-dark text-uppercase fs-7">
                        <tr>
                            <th class="text-center py-3 px-2">No</th>
                            <th class="py-3 px-2">Code</th>
                            <th class="py-3 px-2">Category</th>
                            <th class="text-center py-3 px-2">Image</th>
                            <th class="py-3 px-2">Product Info</th>
                            <th class="text-center py-3 px-2">Price</th>
                            <th class="text-left py-3 px-2">Stock</th>
                            <th class="text-left py-3 px-2">Alert</th>
                            <th class="text-left py-3 px-2">Status</th>
                            <th class="text-lrft py-3 px-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $sql = "SELECT p.*, COALESCE(c.name, 'Unassigned') as cname
                            FROM `product_list` p 
                            LEFT JOIN `category_list` c ON p.category_id = c.category_id 
                            WHERE p.delete_flag = 0 
                            ORDER BY p.`name` ASC";
                    
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->execute();
                        $qry = $stmt->get_result();
                        $i = 1;

                        if ($qry && $qry->num_rows > 0):
                            while($row = $qry->fetch_assoc()):
                                // Updated to match Actions.php path format: images/products/
                                $filename = basename($row['image']);
                                $paths_to_check = [
                                    $row['image'],
                                    'images/products/' . $filename,
                                    'admin/images/products/' . $filename,
                                    'uploads/products/' . $filename,
                                    'admin/uploads/products/' . $filename
                                ];
                                
                                $img_path = 'images/no-image.png';
                                foreach ($paths_to_check as $p) {
                                    if (!empty($row['image']) && file_exists($p)) {
                                        $img_path = $p;
                                        break;
                                    }
                                }

                                $stock = (float)$row['stock'];
                                $alert = (float)$row['alert_restock'];
                    ?>
                    <tr>
                        <td class="text-center py-3 px-2 fw-semibold text-muted"><?php echo $i++; ?></td>
                        <td class="py-3 px-2 font-monospace text-secondary"><?php echo htmlspecialchars($row['product_code']); ?></td>
                        <td class="py-3 px-2 fw-semibold text-dark"><?php echo htmlspecialchars($row['cname']); ?></td>
                        <td class="text-center py-3 px-2"> 
                            <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="img-thumbnail rounded-3 shadow-sm" style="height: 45px; width: 45px; object-fit: cover;">
                        </td>
                        <td class="py-3 px-2">
                            <div class="fw-bold text-dark text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </div>
                            <div class="text-muted small text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($row['description']); ?>">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-end fw-semibold text-black">Rs. <?php echo number_format((float)$row['price'], 2); ?></td>
                        <td class="py-3 px-2 text-center">
                            <?php if ($stock <= $alert):?>
                                <span class="badge bg-gradient px-3 py-2 rounded-pill fs-7 shadow-sm text-white" style="background-color: #ff0000;"">
                                    <i class="fa fa-exclamation-triangle me-1"></i> <?php echo number_format($stock); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success bg-gradient px-3 py-2 rounded-pill fs-7 shadow-sm">
                                    <?php echo number_format($stock); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-2 text-center text-muted fw-semibold"><?php echo number_format($alert); ?></td>
                        <td class="py-3 px-2 text-center">
                            <?php if((int)$row['status'] === 1):?>
                                <span class="badge bg-primary bg-gradient px-3 py-2 rounded-pill fs-7 shadow-sm">Popular</span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-gradient px-3 py-2 rounded-pill fs-7 shadow-sm">Unpopular</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center py-3 px-2">
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 dropdown-toggle shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                    Action
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 py-2">
                                    <li><a class="dropdown-item view_data py-2 px-3" data-id="<?php echo $row['product_id']; ?>" href="javascript:void(0)"><i class="fa fa-eye text-info me-2"></i> View Details</a></li>
                                    <li><a class="dropdown-item edit_data py-2 px-3" data-id="<?php echo $row['product_id']; ?>" href="javascript:void(0)"><i class="fa fa-edit text-primary me-2"></i> Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item delete_data py-2 px-3 text-danger" data-id="<?php echo $row['product_id']; ?>" data-name="<?php echo htmlspecialchars($row['product_code'] . " - " . $row['name']); ?>" href="javascript:void(0)"><i class="fa fa-trash me-2"></i> Delete</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php 
                            endwhile;
                        else:
                    ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted fst-italic">No products listed in database.</td>
                    </tr>
                    <?php 
                        endif;
                        $stmt->close();
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(function(){
        $('#create_new').click(function(){
            uni_modal('Add New Product', "manage_product.php", 'mid-large');
        });
        
        $('.edit_data').click(function(){
            uni_modal('Edit Product Details', "manage_product.php?id=" + $(this).attr('data-id'), 'mid-large');
        });
        
        $('.view_data').click(function(){
            uni_modal('Product Details', "view_product.php?id=" + $(this).attr('data-id'), '');
        });
        
        $('.delete_data').click(function(){
            _conf("Are you sure to delete <b>" + $(this).attr('data-name') + "</b> from Product List?", 'delete_data', [$(this).attr('data-id')]);
        });

        if($.fn.DataTable.isDataTable('#product-table')) {
            $('#product-table').DataTable().destroy();
        }
        
        $('#product-table').DataTable({
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [3, 9] }
            ]
        });
    });

    function delete_data($id){
        $('#confirm_modal button').attr('disabled', true);
        $.ajax({
            url: './Actions.php?a=delete_product',
            method: 'POST',
            data: { id: $id },
            dataType: 'JSON',
            error: function(err){
                console.log(err);
                alert("An error occurred. Check console for details.");
                $('#confirm_modal button').attr('disabled', false);
            },
            success: function(resp){
                if(resp && resp.status == 'success'){
                    location.reload();
                } else {
                    alert(resp.msg || "Deletion failed.");
                    $('#confirm_modal button').attr('disabled', false);
                }
            }
        });
    }
</script>