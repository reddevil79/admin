<?php
require_once("DBConnection.php");
$stmt = $conn->prepare("SELECT * FROM `user_list` WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$qry = $stmt->get_result();
if($qry && $qry->num_rows > 0){
    $row = $qry->fetch_assoc();
    foreach($row as $k => $v){
        $$k = $v;
    }
}
$stmt->close();
?>
<div class="content py-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden col-lg-8 mx-auto">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h3 class="fw-bold text-dark mb-0"><span class="fa fa-user-cog me-2 text-primary"></span>Manage Account</h3>
            <p class="text-muted small mb-0">Update your account credentials and login information.</p>
        </div>
        <div class="card-body p-4 bg-light">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <form action="" id="user-form">
                    <input type="hidden" name="id" value="<?php echo isset($user_id) ? $user_id : ''; ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-envelope text-muted"></i></span>
                            <input type="email" name="email" id="email" required class="form-control rounded-end-3 bg-light border-0 py-2" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="Enter email">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold text-muted">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-user text-muted"></i></span>
                            <input type="text" name="username" id="username" required class="form-control rounded-end-3 bg-light border-0 py-2" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" placeholder="Enter username">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-muted">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-lock text-muted"></i></span>
                            <input type="password" name="password" id="password" class="form-control rounded-end-3 bg-light border-0 py-2" placeholder="Leave blank to keep current password">
                        </div>
                        <div class="form-text text-muted fs-8 mt-1">Leave the New Password field blank if you do not want to update your password.</div>
                    </div>

                    <div class="mb-4">
                        <label for="cpassword" class="form-label small fw-semibold text-muted">Current Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-key text-muted"></i></span>
                            <input type="password" name="cpassword" id="cpassword" class="form-control rounded-end-3 bg-light border-0 py-2" placeholder="Enter current password to confirm changes">
                        </div>
                    </div>

                    <div class="d-flex w-100 justify-content-end">
                        <button type="submit" class="btn btn-primary bg-gradient px-4 py-2 rounded-pill shadow-sm fw-semibold">
                            <i class="fa fa-save me-1"></i> Update Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(function(){
        $('#user-form').submit(function(e){
            e.preventDefault();
            $('.pop_msg').remove();
            var _this = $(this);
            var _el = $('<div>').addClass('pop_msg alert alert-dismissible fade show mb-3');
            
            $('#uni_modal button, button[type="submit"]').attr('disabled', true);
            var $submitBtn = _this.find('button[type="submit"]');
            $submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Updating...');

            $.ajax({
                url: './Actions.php?a=update_credentials',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'JSON',
                error: function(err){
                    console.log(err);
                    _el.addClass('alert-danger').text("An unexpected server error occurred.");
                    _this.prepend(_el);
                    $('#uni_modal button, button[type="submit"]').attr('disabled', false);
                    $submitBtn.html('<i class="fa fa-save me-1"></i> Update Account');
                },
                success: function(resp){
                    if(resp && resp.status === 'success'){
                        _el.addClass('alert-success').text(resp.msg || "Credentials updated successfully.");
                        setTimeout(function(){
                            location.reload();
                        }, 1000);
                    } else {
                        _el.addClass('alert-danger').text(resp && resp.msg ? resp.msg : "An error occurred.");
                        $('#uni_modal button, button[type="submit"]').attr('disabled', false);
                        $submitBtn.html('<i class="fa fa-save me-1"></i> Update Account');
                    }
                    _this.prepend(_el);
                }
            });
        });
    });
</script>
