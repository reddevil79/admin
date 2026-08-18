<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("DBConnection.php");
?>
<div class="content py-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0"><span class="fa fa-tags me-2 text-primary"></span>Category Management</h3>
                <p class="text-muted small mb-0">Organize and manage inventory product categories.</p>
            </div>
            <div>
                <button id="new_category" class="btn btn-primary bg-gradient px-4 py-2 rounded-pill shadow-sm fw-semibold" type="button">
                    <i class="fa fa-plus me-1"></i> Add Category
                </button>
            </div>
        </div>

        <div class="card-body p-4 bg-light">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="category-tbl">
                            <thead class="table-dark text-uppercase fs-7">
                                <tr>
                                    <th class="text-center py-3 px-2" style="width: 8%;">No</th>
                                    <th class="py-3 px-2" style="width: 35%;">Category Name</th>
                                    <th class="text-center py-3 px-2" style="width: 15%;">Products Count</th>
                                    <th class="text-center py-3 px-2" style="width: 15%;">Status</th>
                                    <th class="text-center py-3 px-2" style="width: 27%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Query category list along with active product counts to safely block deletion if products exist
                                $query_str = "SELECT c.*, COUNT(p.product_id) as product_count 
                                              FROM `category_list` c 
                                              LEFT JOIN `product_list` p ON c.category_id = p.category_id AND p.delete_flag = 0 
                                              WHERE c.delete_flag = 0 
                                              GROUP BY c.category_id 
                                              ORDER BY c.name ASC";
                                
                                $stmt = $conn->prepare($query_str);
                                if ($stmt) {
                                    $stmt->execute();
                                    $cat_qry = $stmt->get_result();

                                    $i = 1;
                                    if ($cat_qry && $cat_qry->num_rows > 0):
                                        while ($row = $cat_qry->fetch_assoc()):
                                            $has_products = intval($row['product_count']) > 0;
                                ?>
                                <tr>
                                    <td class="text-center py-3 px-2 fw-semibold text-muted"><?php echo $i++; ?></td>
                                    <td class="py-3 px-2 fw-bold text-dark">
                                        <i class="fa fa-tag text-muted me-1 small"></i><?php echo htmlspecialchars($row['name']); ?>
                                    </td>
                                    <td class="text-center py-3 px-2">
                                        <span class="badge bg-info bg-gradient px-2 py-1"><?php echo number_format($row['product_count']); ?> items</span>
                                    </td>
                                    <td class="text-center py-3 px-2">
                                        <?php if (isset($row['status']) && $row['status'] == 1): ?>
                                            <span class="badge bg-success bg-gradient px-3 py-1 rounded-pill">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-gradient px-3 py-1 rounded-pill">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center py-3 px-2">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="view_category btn btn-outline-info px-2 rounded-start-pill" title="View Details" data-id="<?php echo $row['category_id']; ?>">
                                                <i class="fa fa-th-list"></i>
                                            </button>
                                            <button type="button" class="edit_category btn btn-outline-primary px-2" title="Edit Category" data-id="<?php echo $row['category_id']; ?>">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <?php if ($has_products): ?>
                                                <button type="button" class="btn btn-outline-secondary px-2 rounded-end-pill" title="Cannot delete: Category contains linked products" disabled>
                                                    <i class="fa fa-lock"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="delete_category btn btn-outline-danger px-2 rounded-end-pill" title="Delete Category" data-id="<?php echo $row['category_id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                        endwhile;
                                    else: 
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted fst-italic">No categories listed yet.</td>
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
    </div>
</div>

<script>
    $(function(){
        $('#new_category').click(function(){
            uni_modal('<i class="fa fa-plus-circle me-2"></i>Add New Category', "manage_category.php", 'modal-md');
        });

        $('.edit_category').click(function(){
            uni_modal('<i class="fa fa-edit me-2"></i>Edit Category Details', "manage_category.php?id=" + $(this).attr('data-id'), 'modal-md');
        });

        $('.view_category').click(function(){
            uni_modal('<i class="fa fa-th-list me-2"></i>Category Details', "view_category.php?id=" + $(this).attr('data-id'), 'modal-md');
        });

        $('.delete_category').click(function(){
            _conf("Are you sure to delete category <b>" + $(this).attr('data-name') + "</b>?", 'delete_category', [$(this).attr('data-id')]);
        });

        if ($.fn.DataTable) {
            $('#category-tbl').DataTable({
                columnDefs: [
                    { orderable: false, targets: 4 }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search categories..."
                }
            });
        }
    });

    function delete_category($id){
        $('#confirm_modal button').attr('disabled', true);
        $.ajax({
            url: './Actions.php?a=delete_category',
            method: 'POST',
            data: { id: $id },
            dataType: 'JSON',
            error: function(err){
                console.log(err);
                alert("An unexpected server error occurred.");
                $('#confirm_modal button').attr('disabled', false);
            },
            success: function(resp){
                if (resp && resp.status === 'success') {
                    location.reload();
                } else {
                    alert((resp && resp.msg) ? resp.msg : "An error occurred while deleting.");
                    $('#confirm_modal button').attr('disabled', false);
                }
            }
        });
    }
</script>