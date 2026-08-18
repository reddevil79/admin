<?php
require_once("DBConnection.php");
?>
<div class="content py-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0"><span class="fa fa-users-cog me-2 text-primary"></span>Admin Users Management</h3>
                <p class="text-muted small mb-0">Manage system administrators, credentials, and account permissions.</p>
            </div>
            <div>
                <button class="btn btn-primary bg-gradient px-4 py-2 rounded-pill shadow-sm fw-semibold" type="button" id="create_new">
                    <i class="fa fa-user-plus me-1"></i> Add New User
                </button>
            </div>
        </div>

        <div class="card-body p-4 bg-light">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="user-tbl">
                            <thead class="table-dark text-uppercase fs-7">
                                <tr>
                                    <th class="text-center py-3 px-2" style="width: 8%;">No</th>
                                    <th class="py-3 px-2" style="width: 35%;">Email Address</th>
                                    <th class="py-3 px-2" style="width: 35%;">Username</th>
                                    <th class="text-center py-3 px-2" style="width: 22%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql = "SELECT * FROM `user_list` WHERE user_id != 1 ORDER BY `email` ASC";
                                $qry = $conn->query($sql);
                                $i = 1;
                                if ($qry && $qry->num_rows > 0):
                                    while($row = $qry->fetch_assoc()):
                                ?>
                                <tr>
                                    <td class="text-center py-3 px-2 fw-semibold text-muted"><?php echo $i++; ?></td>
                                    <td class="py-3 px-2 font-monospace text-dark">
                                        <i class="fa fa-envelope text-muted me-1 small"></i><?php echo htmlspecialchars($row['email']); ?>
                                    </td>
                                    <td class="py-3 px-2 fw-bold text-secondary">
                                        <i class="fa fa-user text-muted me-1 small"></i><?php echo htmlspecialchars($row['username']); ?>
                                    </td>
                                    <td class="text-center py-3 px-2">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-sm px-3 rounded-pill dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu shadow-sm border-0 rounded-3">
                                                <li>
                                                    <a class="dropdown-item edit_data py-2" data-id="<?php echo $row['user_id']; ?>" href="javascript:void(0)">
                                                        <i class="fa fa-edit text-primary me-2"></i> Edit Details
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <a class="dropdown-item delete_data py-2 text-danger" data-id="<?php echo $row['user_id']; ?>" data-name="<?php echo htmlspecialchars($row['email']); ?>" href="javascript:void(0)">
                                                        <i class="fa fa-trash text-danger me-2"></i> Delete Account
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted fst-italic">No additional system users found.</td>
                                </tr>
                                <?php endif; ?>
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
        $('#create_new').click(function(){
            uni_modal('<i class="fa fa-user-plus me-2"></i>Add New Admin User', "manage_user.php", 'modal-md');
        });

        $('.edit_data').click(function(){
            uni_modal('<i class="fa fa-user-edit me-2"></i>Edit Admin User', "manage_user.php?id=" + $(this).attr('data-id'), 'modal-md');
        });

        $('.delete_data').click(function(){
            _conf("Are you sure you want to delete user account <b>" + $(this).attr('data-name') + "</b>?", 'delete_user', [$(this).attr('data-id')]);
        });

        // Initialize DataTable if plugin is active
        if ($.fn.DataTable) {
            $('#user-tbl').DataTable({
                columnDefs: [
                    { orderable: false, targets: 3 }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search users..."
                }
            });
        }
    });

    function delete_user($id){
        $('#confirm_modal button').attr('disabled', true);
        $.ajax({
            url: './Actions.php?a=delete_user',
            method: 'POST',
            data: { id: $id },
            dataType: 'JSON',
            error: function(err){
                console.log(err);
                alert("An unexpected server error occurred.");
                $('#confirm_modal button').attr('disabled', false);
            },
            success: function(resp){
                if(resp && resp.status === 'success'){
                    location.reload();
                } else {
                    alert((resp && resp.msg) ? resp.msg : "Failed to delete user record.");
                    $('#confirm_modal button').attr('disabled', false);
                }
            }
        });
    }
</script>