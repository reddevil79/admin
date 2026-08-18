<?php
require_once("DBConnection.php");
?>
<div class="content py-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0"><span class="fa fa-cash-register me-2 text-primary"></span>Point of Sale Transaction</h3>
                <p class="text-muted small mb-0">Select items from the catalog to generate a new customer sale receipt.</p>
            </div>
            <div>
                <button class="btn btn-primary bg-gradient px-4 py-2 rounded-pill shadow-sm fw-semibold" id="transaction-save-btn" type="button">
                    <i class="fa fa-check-circle me-1"></i> Proceed to Payment
                </button>
            </div>
        </div>
        
        <div class="card-body p-4 bg-light">
            <style>
                #plist .item, #item-list tr {
                    cursor: pointer;
                    transition: background-color 0.15s ease-in-out;
                }
                #plist .item:hover {
                    background-color: rgba(13, 110, 253, 0.075) !important;
                }
            </style>

            <form action="" id="transaction-form" class="h-100">
                <div class="row g-4">
                    <!-- Left Column: Product Selection Catalog -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                            <div class="card-body p-3 d-flex flex-column h-100">
                                <h5 class="fw-bold text-dark mb-3">Product Catalog</h5>
                                
                                <!-- Search Bar -->
                                <div class="input-group input-group-sm mb-3">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa fa-search text-muted"></i></span>
                                    <input type="text" autocomplete="off" class="form-control border-start-0 bg-light py-2" id="search" placeholder="Type product code, name, or category...">
                                </div>

                                <!-- Table Headers -->
                                <div class="table-responsive flex-grow-1" style="max-height: 55vh; min-height: 55vh;">
                                    <table class="table table-hover align-middle mb-0" id="plist">
                                        <thead class="table-dark text-uppercase fs-7 sticky-top">
                                            <tr>
                                                <th class="py-2 px-2">Category</th>
                                                <th class="py-2 px-2">Code</th>
                                                <th class="py-2 px-2 text-center">Image</th>
                                                <th class="py-2 px-2">Product Name</th>
                                                <th class="py-2 px-2 text-left">Price</th>
                                                <th class="py-2 px-2 text-end">Stock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                        $sql = "SELECT p.*, COALESCE(c.name, 'Unassigned') as cname
                                                FROM `product_list` p
                                                LEFT JOIN `category_list` c ON p.category_id = c.category_id
                                                WHERE p.delete_flag = 0 
                                                ORDER BY p.`name` ASC";
                                        
                                        $qry = $conn->query($sql);
                                        if ($qry && $qry->num_rows > 0):
                                            while($row = $qry->fetch_assoc()):
                                                $stock = (float)$row['stock'];
                                                $alert = (float)$row['alert_restock'];
                                                $raw_price = (float)$row['price'];

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
                                        ?>
                                        <tr class="item <?php echo $stock <= $alert ? 'table-danger' : ''; ?>" data-id="<?php echo $row['product_id']; ?>">
                                            <td class="py-2 px-2 fw-semibold text-secondary"><?php echo htmlspecialchars($row['cname']); ?></td>
                                            <td class="py-2 px-2 font-monospace text-muted"><?php echo htmlspecialchars($row['product_code']); ?></td>
                                            <td class="text-center py-2 px-2"> 
                                                <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="img-thumbnail rounded-2 shadow-sm" style="height: 38px; width: 38px; object-fit: cover;">
                                            </td>
                                            <td class="py-2 px-2 fw-bold text-dark pname"><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td class="py-2 px-2 text-end fw-bold text-black price" data-raw-price="<?php echo $raw_price; ?>"><?php echo number_format($raw_price, 2); ?></td>
                                            <td class="py-2 px-2 text-end qty">
                                                <span class="badge <?php echo $stock <= $alert ? 'bg-danger' : 'bg-secondary'; ?> badge bg-gradient px-3 py-2 rounded-pill fs-7 shadow-sm text-white" style="background-color: #ff0000;">
                                                    <?php echo number_format($stock); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted fst-italic">No active products found in inventory.</td>
                                        </tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Cart Items & Totals Summary -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                            <div class="card-body p-3 d-flex flex-column h-100">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="fw-bold text-dark mb-0">Current Order</h5>
                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm" type="button" id="remove-item" disabled onclick="remove_item()">
                                        <i class="fa fa-trash me-1"></i> Remove
                                    </button>
                                </div>

                                <!-- Cart Item Table -->
                                <div class="table-responsive flex-grow-1 border rounded-3 mb-3 bg-light" style="max-height: 32vh; min-height: 32vh;">
                                    <table class="table table-hover align-middle m-0" id="item-list">
                                        <thead class="table-light text-uppercase fs-7 sticky-top">
                                            <tr>
                                                <th class="py-2 px-2 text-center" style="width: 25%;">Qty</th>
                                                <th class="py-2 px-2" style="width: 50%;">Product</th>
                                                <th class="py-2 px-2 text-end" style="width: 25%;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <!-- Financial Breakdown & Discount Type Option -->
                                <div class="bg-light p-3 rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted fw-semibold small">Subtotal</span>
                                        <span class="fw-bold fs-6 text-dark" id="subTotal">Rs. 0.00</span>
                                    </div>
                                    
                                    <!-- Discount Input Controls -->
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted fw-semibold small">Discount</span>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <input type="radio" class="btn-check" name="discount_type_toggle" id="disc_type_percent" value="percent" checked>
                                                <label class="btn btn-outline-secondary py-0 px-2 fs-7" for="disc_type_percent">%</label>
                                                <input type="radio" class="btn-check" name="discount_type_toggle" id="disc_type_fixed" value="fixed">
                                                <label class="btn btn-outline-secondary py-0 px-2 fs-7" for="disc_type_fixed">Rs.</label>
                                            </div>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="any" min="0" class="form-control text-end fw-bold" id="discount_input" value="0" placeholder="0">
                                            <span class="input-group-text bg-white fw-bold" id="discount_unit_label">%</span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted fw-semibold small">Discount Amount</span>
                                        <span class="fw-bold fs-6 text-danger" id="summaryDiscount">-Rs. 0.00</span>
                                    </div>

                                    <hr class="my-2 opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">Final Payable</span>
                                        <span class="fw-bold fs-5 text-primary" id="totalDisplay">Rs. 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden inputs matched directly to action.php's save_transaction() requirements -->
                <input type="hidden" name="sub_total" value="0">
                <input type="hidden" name="total" value="0">
                <input type="hidden" name="discount_type" value="percent">
                <input type="hidden" name="discount_amount" value="0">
                <input type="hidden" name="discount_percent" value="0">
                <input type="hidden" name="tendered_amount" value="0">
                <input type="hidden" name="change" value="0">
            </form>
        </div>
    </div>
</div>

<script>
    $(function(){
        $('#search').on('input', function(){
            var _search = $(this).val().toLowerCase();
            $('#plist tbody tr.item').each(function(){
                var _text = $(this).text().toLowerCase();
                $(this).toggle(_text.includes(_search));
            });
        });

        $('#plist tbody tr.item').click(function(){
            var _tr = $(this);
            var pid = _tr.attr('data-id');
            var pcode = _tr.find('.font-monospace').text();
            var name = _tr.find('.pname').text();
            
            var price = parseFloat(_tr.find('.price').attr('data-raw-price')) || 0;
            var maxStock = parseInt(_tr.find('.qty').text().replace(/,/g, '')) || 0;
            var qty = 1;

            if (maxStock <= 0) {
                alert("This item is currently out of stock.");
                return false;
            }

            var existingRow = $('#item-list tbody tr[data-id="' + pid + '"]');
            if (existingRow.length > 0) {
                var currentQty = parseFloat(existingRow.find('[name="quantity[]"]').val()) || 0;
                if ((currentQty + 1) > maxStock) {
                    alert("Cannot add more than available stock (" + maxStock + ").");
                    return false;
                }
                existingRow.find('[name="quantity[]"]').val(currentQty + 1).trigger('input');
                return false;
            }

            var ntr = $("<tr>").attr('data-id', pid).attr('tabindex', '0');
            ntr.append('<td class="py-2 px-2 align-middle text-center">' +
                '<input class="form-control form-control-sm text-center fw-bold rounded-2" type="number" name="quantity[]" min="1" max="' + maxStock + '" value="' + qty + '"/>' +
                '<input type="hidden" name="product_id[]" value="' + pid + '"/>' +
                '<input type="hidden" name="price[]" value="' + price + '"/>' +
                '</td>');
            ntr.append('<td class="py-2 px-2 align-middle">' +
                '<div class="fw-bold text-dark text-truncate" style="max-width: 150px;" title="' + name + '">' + name + '</div>' +
                '<div class="text-muted fs-7 font-monospace">Code: ' + pcode + '</div>' +
                '<div class="text-success fs-7 fw-semibold">Rs. ' + price.toFixed(2) + ' each</div>' +
                '</td>');
            ntr.append('<td class="py-2 px-2 align-middle text-end fw-bold text-dark total" data-val="' + price + '">Rs. ' + price.toFixed(2) + '</td>');

            $('#item-list tbody').append(ntr);
            compute(ntr);
            calculate_total();
        });

        $('input[name="discount_type_toggle"]').change(function(){
            var type = $(this).val();
            $('[name="discount_type"]').val(type);
            $('#discount_unit_label').text(type === 'percent' ? '%' : 'Rs.');
            calculate_total();
        });

        $('#discount_input').on('input keyup', function(){
            calculate_total();
        });

        $('#transaction-save-btn').click(function(){
            if ($('#item-list tbody tr').length <= 0) {
                alert("Please add at least 1 item to the order before proceeding.");
                return false;
            }
            var subtotal = $('[name="sub_total"]').val() || 0;
            var finalPayable = $('[name="total"]').val() || 0;
            var discVal = $('#discount_input').val() || 0;
            var discType = $('[name="discount_type"]').val() || 'percent';

            uni_modal("Payment Checkout", "tender_amount.php?amount=" + finalPayable + "&subtotal=" + subtotal + "&disc_val=" + discVal + "&disc_type=" + discType, 'modal-md');
        });

        $('#transaction-form input').keydown(function(e){
            if (e.which === 13) {
                e.preventDefault();
                return false;
            }
        });
    });

    function compute(_this){
        _this.find('[name="quantity[]"]').on('input keydown', function(){
            var qty = parseFloat($(this).val()) || 0;
            var price = parseFloat(_this.find('[name="price[]"]').val()) || 0;
            var _total = qty * price;

            _this.find('.total').attr('data-val', _total).text('Rs. ' + _total.toFixed(2));
            calculate_total();
        });

        _this.find('[name="quantity[]"]').on('focusout', function(){
            if ($(this).val() <= 0 || $(this).val() === '') {
                $(this).val('1').trigger('input');
            }
        });

        _this.on('click focusin', function(){
            $('#item-list tr').removeClass("table-active selected-item");
            $(this).addClass("table-active selected-item");
            $('#remove-item').attr('disabled', false);
        });
    }

    function calculate_total(){
        var sub = 0;
        $('#item-list tr .total').each(function(){
            var val = parseFloat($(this).attr('data-val')) || 0;
            sub += val;
        });

        var discInput = parseFloat($('#discount_input').val()) || 0;
        var discType = $('[name="discount_type"]').val();
        var discountAmount = 0;
        var discountPercent = 0;

        if (discType === 'percent') {
            discountPercent = Math.min(100, Math.max(0, discInput));
            discountAmount = (sub * discountPercent) / 100;
        } else {
            discountAmount = Math.min(sub, Math.max(0, discInput));
            discountPercent = sub > 0 ? (discountAmount / sub) * 100 : 0;
        }

        var finalPayable = Math.max(0, sub - discountAmount);

        $('[name="sub_total"]').val(sub.toFixed(2));
        $('[name="total"]').val(finalPayable.toFixed(2)); // total field now stores the net payable amount required by action.php
        $('[name="discount_amount"]').val(discountAmount.toFixed(2));
        $('[name="discount_percent"]').val(discountPercent.toFixed(2));

        $('#subTotal').text('Rs. ' + sub.toFixed(2));
        $('#summaryDiscount').text('-Rs. ' + discountAmount.toFixed(2) + (discType === 'percent' ? ' (' + discountPercent.toFixed(1) + '%)' : ''));
        $('#totalDisplay').text('Rs. ' + finalPayable.toFixed(2));
    }

    function remove_item(){
        $('#item-list tr.selected-item').remove();
        calculate_total();
        $('#remove-item').attr('disabled', true);
    }
</script>