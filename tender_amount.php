<?php
$amount     = isset($_GET['amount']) && is_numeric($_GET['amount']) ? floatval($_GET['amount']) : 0;
$subtotal   = isset($_GET['subtotal']) && is_numeric($_GET['subtotal']) ? floatval($_GET['subtotal']) : $amount;
$disc_val   = isset($_GET['disc_val']) && is_numeric($_GET['disc_val']) ? floatval($_GET['disc_val']) : 0;
$disc_type  = isset($_GET['disc_type']) ? $_GET['disc_type'] : 'percent';

if ($disc_type === 'percent') {
    $disc_percent_display = number_format($disc_val, 1) . '%';
    $disc_amount_display  = number_format(($subtotal * $disc_val) / 100, 2);
} else {
    $disc_percent_display = $subtotal > 0 ? number_format(($disc_val / $subtotal) * 100, 1) . '%' : '0.0%';
    $disc_amount_display  = number_format($disc_val, 2);
}
?>
<style>
    #uni_modal .modal-footer {
        display: none !important;
    }
</style>
<div class="container-fluid py-2">
    <div class="card border-0 shadow-none">
        <div class="card-body p-0">
            
            <!-- Success Message Banner (Hidden by default) -->
            <div id="save_success_alert" class="alert alert-success d-flex align-items-center mb-3" style="display: none !important;" role="alert">
                <i class="fa fa-check-circle fs-4 me-2"></i>
                <div>
                    <strong>Transaction Saved Successfully!</strong><br>
                    You can now print the receipt or complete the checkout.
                </div>
            </div>

            <!-- Subtotal & Applied Discount Summary -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label fs-7 fw-bold text-muted mb-1">Subtotal</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 fw-bold text-muted">Rs.</span>
                        <input type="text" class="form-control text-end bg-light border-start-0 fw-bold" value="<?php echo number_format($subtotal, 2); ?>" readonly>
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label fs-7 fw-bold text-muted mb-1">Applied Discount (<?php echo htmlspecialchars($disc_percent_display); ?>)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 fw-bold text-danger">-Rs.</span>
                        <input type="text" class="form-control text-end bg-light border-start-0 fw-bold text-danger" value="<?php echo htmlspecialchars($disc_amount_display); ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Final Payable Amount -->
            <div class="form-group mb-3">
                <label for="amount" class="form-label fs-5 fw-bold text-primary">Final Payable Amount</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-primary bg-opacity-10 fw-bold text-primary border-primary">Rs.</span>
                    <input type="text" id="amount" class="form-control text-end bg-primary bg-opacity-10 fw-bold text-primary border-primary" value="<?php echo number_format($amount, 2, '.', ''); ?>" readonly>
                </div>
            </div>

            <!-- Tendered Amount -->
            <div class="form-group mb-3">
                <label for="tender" class="form-label fs-5 fw-bold text-dark">Tendered Amount</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light fw-bold">Rs.</span>
                    <input type="number" step="any" min="0" id="tender" class="form-control text-end fw-bold" value="<?php echo $amount > 0 ? htmlspecialchars($amount) : '0'; ?>" autofocus>
                </div>
                <div id="tender_error" class="text-danger small mt-1 fw-semibold" style="display: none;">
                    <i class="fa fa-exclamation-triangle me-1"></i> Tendered amount is insufficient.
                </div>
            </div>

            <!-- Change -->
            <div class="form-group mb-4">
                <label for="change" class="form-label fs-5 fw-bold text-dark">Change</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light fw-bold">Rs.</span>
                    <input type="text" id="change" class="form-control text-end bg-light fw-bold" value="0.00" readonly>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="w-100 d-flex justify-content-end gap-2 pt-2 border-top">
                <button class="btn btn-secondary px-4 rounded-pill fw-semibold" type="button" data-bs-dismiss="modal" id="btn_close_modal">Close</button>
                
                <!-- Save Button -->
                <button class="btn btn-primary bg-gradient px-4 rounded-pill fw-semibold shadow-sm" type="button" id="save_trans">
                    <i class="fa fa-check-circle me-1"></i> Save Amount
                </button>

                <!-- Print Bill Button (Initially Hidden) -->
                <button class="btn btn-success bg-gradient px-4 rounded-pill fw-semibold shadow-sm" type="button" id="print_bill_btn" style="display: none;">
                    <i class="fa fa-print me-1"></i> Print Bill
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function(){
        var $parentForm = $('#transaction-form');
        var savedTransactionId = null;

        // Synchronize required hidden inputs on the main POS transaction form.
        // These fields carry subtotal/discount/payable data through to
        // Actions.php::save_transaction() via FormData($parentForm[0]) below,
        // so every one of them must exist before the form is ever submitted.
        if($parentForm.find('[name="tendered_amount"]').length === 0) {
            $parentForm.append('<input type="hidden" name="tendered_amount" value="<?php echo $amount; ?>">');
        }
        if($parentForm.find('[name="change"]').length === 0) {
            $parentForm.append('<input type="hidden" name="change" value="0">');
        }
        if($parentForm.find('[name="sub_total"]').length === 0) {
            $parentForm.append('<input type="hidden" name="sub_total" value="<?php echo $subtotal; ?>">');
        }
        if($parentForm.find('[name="total"]').length === 0) {
            $parentForm.append('<input type="hidden" name="total" value="<?php echo $amount; ?>">');
        }
        if($parentForm.find('[name="discount_type"]').length === 0) {
            $parentForm.append('<input type="hidden" name="discount_type" value="<?php echo htmlspecialchars($disc_type); ?>">');
        }
        if($parentForm.find('[name="discount_amount"]').length === 0) {
            $parentForm.append('<input type="hidden" name="discount_amount" value="<?php echo $disc_amount_display; ?>">');
        }
        if($parentForm.find('[name="discount_percent"]').length === 0) {
            var _fallbackPercent = <?php echo $disc_type === 'percent' ? json_encode($disc_val) : json_encode($subtotal > 0 ? round(($disc_val / $subtotal) * 100, 2) : 0); ?>;
            $parentForm.append('<input type="hidden" name="discount_percent" value="' + _fallbackPercent + '">');
        }

        $('#uni_modal').off('shown.bs.modal').on('shown.bs.modal', function(){
            $('#tender').trigger('focus').select();
        });

        $('#tender').on('keydown', function(e){
            if(e.which === 13){
                e.preventDefault();
                if ($('#save_trans').is(':visible')) {
                    $('#save_trans').trigger('click');
                } else if ($('#print_bill_btn').is(':visible')) {
                    $('#print_bill_btn').trigger('click');
                }
            }
        });

        function calculateTotals() {
            var finalPayable = parseFloat('<?php echo $amount; ?>') || 0;
            var rawTender = $('#tender').val();
            var tender = parseFloat(rawTender) > 0 ? parseFloat(rawTender) : 0;
            
            // Sync form fields
            $parentForm.find('[name="total"]').val(finalPayable.toFixed(2));
            $parentForm.find('[name="tendered_amount"]').val(tender.toFixed(2));
            
            var change = tender - finalPayable;
            
            $parentForm.find('[name="change"]').val(change.toFixed(2));
            $('#change').val(change.toFixed(2));

            if (change < 0 && rawTender !== '') {
                $('#change').addClass('is-invalid text-danger');
                $('#tender_error').show();
            } else {
                $('#change').removeClass('is-invalid text-danger');
                $('#tender_error').hide();
            }
        }

        $('#tender').on('keypress input', function(){
            calculateTotals();
        });

        $('#save_trans').click(function(){
            $('#change').removeClass('is-invalid text-danger');
            $('#tender_error').hide();

            var finalPayable = parseFloat($('#amount').val()) || 0;
            var tenderVal = parseFloat($parentForm.find('[name="tendered_amount"]').val()) || 0;
            var changeVal = parseFloat($parentForm.find('[name="change"]').val()) || 0;

            if (changeVal < 0) {
                $('#change').addClass('is-invalid text-danger');
                $('#tender_error').html('<i class="fa fa-exclamation-triangle me-1"></i> Tendered amount is less than payable amount.').show();
                $('#tender').focus();
            } else if (tenderVal <= 0 && finalPayable > 0) {
                $('#tender_error').html('<i class="fa fa-exclamation-triangle me-1"></i> Please enter a valid tendered amount.').show();
                $('#tender').focus();
            } else {
                $('#save_trans').attr('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: './Actions.php?a=save_transaction',
                    method: 'POST',
                    data: new FormData($parentForm[0]),
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(response){
                        var resp = {};
                        try {
                            resp = (typeof response === 'object') ? response : JSON.parse(response);
                        } catch(e) {
                            if(!isNaN(parseInt(response))) {
                                resp = { status: 'success', transaction_id: parseInt(response) };
                            } else {
                                resp = {};
                            }
                        }

                        savedTransactionId = resp.transaction_id || resp.id;

                        if (resp.status === 'success' || savedTransactionId) {
                            // Lock inputs
                            $('#tender').attr('readonly', true);
                            
                            // Show success message banner
                            $('#save_success_alert').attr('style', 'display: flex !important;');
                            
                            // Hide "Save Amount" button and display "Print Bill" button
                            $('#save_trans').hide();
                            $('#print_bill_btn').show().focus();

                            // Reload underlying POS page state when modal is closed
                            $('#uni_modal').off('hidden.bs.modal').on('hidden.bs.modal', function () {
                                location.reload();
                            });
                        } else {
                            alert(resp.msg || 'Failed to save transaction.');
                            $('#save_trans').attr('disabled', false).html('<i class="fa fa-check-circle me-1"></i> Save Amount');
                        }
                    },
                    error: function(err){
                        console.error(err);
                        alert('An error occurred while saving.');
                        $('#save_trans').attr('disabled', false).html('<i class="fa fa-check-circle me-1"></i> Save Amount');
                    }
                });
            }
        });

        // Handler for Print Bill Button
        $('#print_bill_btn').click(function(){
            if (savedTransactionId) {
                if (typeof uni_modal === 'function') {
                    uni_modal("RECEIPT", "view_receipt.php?id=" + savedTransactionId, "mid-large");
                } else if (typeof window.uni_modal === 'function') {
                    window.uni_modal("RECEIPT", "view_receipt.php?id=" + savedTransactionId, "mid-large");
                } else {
                    window.open("view_receipt.php?id=" + savedTransactionId, "_blank");
                }
            }
        });

        calculateTotals();
    });
</script>