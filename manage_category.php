<?php
require_once("DBConnection.php");

$category_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
$name = '';
$description = '';
$status = 1;

if ($category_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM `category_list` WHERE category_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $data = $res->fetch_assoc();
            $name = $data['name'] ?? '';
            $description = $data['description'] ?? '';
            $status = $data['status'] ?? 1;
        }
        $stmt->close();
    }
}
?>
<div class="container-fluid py-2">
    <form action="" id="category-form">
        <input type="hidden" name="id" value="<?php echo $category_id > 0 ? $category_id : ''; ?>">
        
        <div class="mb-3">
            <label for="name" class="form-label small fw-semibold text-muted">Category Name</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fa fa-tag text-muted"></i></span>
                <input type="text" name="name" id="name" autofocus required class="form-control rounded-end-3 bg-light border-0 py-2" value="<?php echo htmlspecialchars($name); ?>" placeholder="Enter category name">
            </div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label small fw-semibold text-muted">Description</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-0 align-items-start pt-2"><i class="fa fa-align-left text-muted"></i></span>
                <textarea name="description" id="description" cols="30" rows="3" class="form-control rounded-end-3 bg-light border-0 py-2" required placeholder="Enter category description"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label small fw-semibold text-muted">Status</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-0"><i class="fa fa-toggle-on text-muted"></i></span>
                <select name="status" id="status" class="form-select rounded-end-3 bg-light border-0 py-2" required>
                    <option value="1" <?php echo $status == 1 ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $status == 0 ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>
    </form>
</div>

<script>
    $(function(){
        $('#category-form').submit(function(e){
            e.preventDefault();
            $('.pop_msg').remove();
            var _this = $(this);
            var _el = $('<div>').addClass('pop_msg alert alert-dismissible fade show mb-3');

            $('#uni_modal button').attr('disabled', true);
            $('#uni_modal button[type="submit"]').html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: './Actions.php?a=save_category',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'JSON',
                error: function(err){
                    console.log(err);
                    _el.addClass('alert-danger').text("An error occurred while saving category.");
                    _this.prepend(_el);
                    $('#uni_modal button').attr('disabled', false);
                    $('#uni_modal button[type="submit"]').text('Save');
                },
                success: function(resp){
                    if (resp && resp.status == 'success') {
                        _el.addClass('alert-success').text(resp.msg || "Category saved successfully.");
                        $('#uni_modal').on('hide.bs.modal', function(){
                            location.reload();
                        });
                        if (parseInt($('input[name="id"]').val() || 0) <= 0) {
                            _this.get(0).reset();
                        }
                    } else {
                        _el.addClass('alert-danger').text(resp && resp.msg ? resp.msg : "An error occurred.");
                    }
                    _this.prepend(_el);
                    $('#uni_modal button').attr('disabled', false);
                    $('#uni_modal button[type="submit"]').text('Save');
                }
            });
        });
    });
</script>
