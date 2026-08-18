<?php
require_once("DBConnection.php");
if(isset($_GET['id'])){
    $stmt = $conn->prepare("SELECT * FROM `user_list` WHERE user_id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $qry = $stmt->get_result();
    if($qry && $qry->num_rows > 0){
        $row = $qry->fetch_assoc();
        foreach($row as $k => $v){
            $$k = $v;
        }
    }
    $stmt->close();
}
?>
<div class="container-fluid py-2">
    <form action="" id="user-form">
        <input type="hidden" name="id" value="<?php echo isset($user_id) ? $user_id : ''; ?>">
        
        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold text-muted">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fa fa-envelope text-muted"></i></span>
                <input type="email" name="email" id="email" required class="form-control rounded-end-3 bg-light border-0 py-2" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="Enter email address">
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
            <label for="password" class="form-label small fw-semibold text-muted">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fa fa-lock text-muted"></i></span>
                <input type="password" name="password" id="password" class="form-control rounded-end-3 bg-light border-0 py-2" placeholder="<?php echo isset($user_id) ? 'Leave blank to keep current password' : 'Enter password'; ?>">
            </div>
            <?php if(isset($user_id)): ?>
                <div class="form-text text-muted fs-8 mt-1">Only fill this out if you wish to change the account password.</div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    $(function(){
        $('#user-form').submit(function(e){
            e.preventDefault();
            $('.pop_msg').remove();
            var _this = $(this);
            var _el = $('<div>').addClass('pop_msg alert alert-dismissible fade show mb-3');
            
            $('#uni_modal button').attr('disabled', true);
            $('#uni_modal button[type="submit"]').html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: './Actions.php?a=edit_user',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'JSON',
                error: function(err){
                    console.log(err);
                    _el.addClass('alert-danger').text("An unexpected server error occurred.");
                    _this.prepend(_el);
                    $('#uni_modal button').attr('disabled', false);
                    $('#uni_modal button[type="submit"]').text('Save');
                },
                success: function(resp){
                    if(resp && resp.status === 'success'){
                        _el.addClass('alert-success').text(resp.msg || "User details saved successfully.");
                        $('#uni_modal').on('hide.bs.modal', function(){
                            location.reload();
                        });
                        if("<?php echo isset($user_id) ? $user_id : ''; ?>" === "") {
                            _this.get(0).reset();
                        }
                    } else {
                        _el.addClass('alert-danger').text(resp && resp.msg ? resp.msg : "An error occurred while saving.");
                    }
                    _this.prepend(_el);
                    $('#uni_modal button').attr('disabled', false);
                    $('#uni_modal button[type="submit"]').text('Save');
                }
            });
        });
    });
</script>
