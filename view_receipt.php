<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("DBConnection.php");

// Fetch transaction ID safely from request
$transaction_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
$transaction = null;

if ($transaction_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM `transaction_list` WHERE transaction_id = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $transaction = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$transaction):
?>
    <div class="container-fluid py-3">
        <div class="alert alert-danger mb-0 rounded-3 shadow-sm">
            <i class="fa fa-exclamation-triangle me-2"></i>Transaction record not found or invalid request ID.
        </div>
        <div class="w-100 d-flex justify-content-end mt-3">
            <button class="btn btn-sm btn-secondary rounded-pill px-4" type="button" data-bs-dismiss="modal" onclick="location.reload()">Close</button>
        </div>
    </div>
<?php 
    exit;
endif;

if (!function_exists('format_num')) {
    function format_num($number) {
        return number_format((float)$number, 2);
    }
}

// --- Line items are fetched ONLY to render the itemized list on the ---
// --- receipt. They are never used to recompute subtotal/discount/   ---
// --- payable/change - those figures come exclusively from the      ---
// --- stored transaction_list row written at save time.             ---
$items_data = [];

$item_stmt = $conn->prepare("SELECT i.*, p.name as pname, p.product_code 
                             FROM `transaction_items` i 
                             INNER JOIN `product_list` p ON i.product_id = p.product_id 
                             WHERE i.transaction_id = ?");
$item_stmt->bind_param("i", $transaction_id);
$item_stmt->execute();
$items = $item_stmt->get_result();

while ($row = $items->fetch_assoc()) {
    $item_qty = (float)($row['quantity'] ?? 0);
    $item_price = (float)($row['price'] ?? 0);
    $row['calculated_amount'] = $item_qty * $item_price;
    $items_data[] = $row;
}
$item_stmt->close();

// --- Every financial figure below is read verbatim from the stored ---
// --- transaction row. Nothing here is recalculated. ---
$subtotal       = (float)($transaction['sub_total'] ?? 0);
$disc_type      = ($transaction['discount_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
$disc_amount    = (float)($transaction['discount_amount'] ?? 0);
$disc_percent   = (float)($transaction['discount_percent'] ?? 0);
$payable_amount = (float)($transaction['total'] ?? 0);
$tendered       = (float)($transaction['tendered_amount'] ?? 0);
$change         = (float)($transaction['change'] ?? 0);
?>
<style>
    #uni_modal .modal-footer {
        display: none !important;
    }

    @media print {
        @page {
            margin: 0;
            size: auto;
        }
        body * {
            visibility: hidden;
        }
        #outprint_receipt, #outprint_receipt * {
            visibility: visible;
        }
        #outprint_receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100% !important;
            max-width: 80mm !important;
            padding: 5px !important;
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
            background: #fff !important;
            color: #000 !important;
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 11px !important;
        }
        .no-print {
            display: none !important;
        }
        .receipt-header, .receipt-footer {
            text-align: center !important;
        }
        .dotted-line {
            border-bottom: 1px dashed #000 !important;
            margin: 4px 0 !important;
        }
        .table-receipt {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .table-receipt th, .table-receipt td {
            padding: 2px 0 !important;
            border: none !important;
        }
    }
</style>

<div class="container-fluid py-2">
    <!-- Receipt Printable Container -->
    <div id="outprint_receipt" class="bg-white p-3 rounded-3 border shadow-sm mx-auto" style="max-width: 380px;">
        <div class="receipt-header text-center fw-bold lh-1 mb-2 pb-2 border-bottom">
            <span class="fs-5 text-dark">Donut Pasal</span><br>
            <small class="text-muted text-uppercase fs-7 tracking-wider">Bakery Sales Receipt</small>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-1 small">
            <span class="text-muted fw-semibold">Date & Time:</span> 
            <span class="fw-bold text-dark font-monospace">
                <?php echo !empty($transaction['date_added']) ? date("Y-m-d H:i", strtotime($transaction['date_added'])) : date("Y-m-d H:i"); ?>
            </span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2 small">
            <span class="text-muted fw-semibold">Receipt No:</span> 
            <span class="font-monospace fw-bold text-primary">
                <?php echo htmlspecialchars($transaction['receipt_no'] ?? ('#TRX-' . $transaction_id)); ?>
            </span>
        </div>

        <div class="dotted-line"></div>

        <!-- Purchased Items Table -->
        <table class="table table-sm align-middle border-0 mb-2 table-receipt" style="font-size: 0.85rem;">
            <thead>
                <tr class="border-bottom">
                    <th class="py-1 px-1 text-center" style="width: 15%;">QTY</th>
                    <th class="py-1 px-1" style="width: 55%;">Item</th>
                    <th class="py-1 px-1 text-end" style="width: 30%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_data as $row): ?>
                <tr>
                    <td class="px-1 py-1 text-center fw-bold align-top"><?php echo (float)$row['quantity']; ?></td>
                    <td class="px-1 py-1 align-top">
                        <div class="lh-sm">
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['pname']); ?></div>
                            <small class="text-muted font-monospace">@ Rs. <?php echo format_num($row['price']); ?></small>
                        </div>
                    </td>
                    <td class="px-1 py-1 text-end fw-semibold text-dark align-top font-monospace">Rs. <?php echo format_num($row['calculated_amount']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="dotted-line"></div>

        <!-- Financial Breakdown (all values read directly from the stored transaction row) -->
        <div class="pt-1">
            <!-- Subtotal -->
            <div class="d-flex justify-content-between align-items-center mb-1 small font-monospace">
                <span class="text-muted">Subtotal:</span>
                <span class="fw-bold text-dark">Rs. <?php echo format_num($subtotal); ?></span>
            </div>

            <!-- Discount Amount -->
            <div class="d-flex justify-content-between align-items-center mb-1 small font-monospace">
                <span class="text-muted">
                    Discount Amount <?php echo ($disc_type === 'percent') ? '(' . format_num($disc_percent) . '%)' : '(Flat)'; ?>:
                </span>
                <span class="fw-bold text-danger">-Rs. <?php echo format_num($disc_amount); ?></span>
            </div>

            <!-- Final Payable Amount -->
            <div class="d-flex justify-content-between align-items-center my-1 pt-1 border-top border-bottom fw-bold font-monospace">
                <span class="text-dark">Payable Amount:</span>
                <span class="fs-6 text-primary">Rs. <?php echo format_num($payable_amount); ?></span>
            </div>

            <!-- Tendered Amount -->
            <div class="d-flex justify-content-between align-items-center mb-1 small font-monospace">
                <span class="text-muted">Tendered Amount:</span>
                <span class="fw-bold text-dark">Rs. <?php echo format_num($tendered); ?></span>
            </div>

            <!-- Change -->
            <div class="d-flex justify-content-between align-items-center small font-monospace">
                <span class="text-muted">Change:</span>
                <span class="fw-bold text-success">Rs. <?php echo format_num($change); ?></span>
            </div>
        </div>

        <div class="receipt-footer text-center mt-3 pt-2 border-top text-muted small fst-italic">
            THANK YOU! PLEASE VISIT AGAIN
        </div>
    </div>
                
    <!-- Action Control Buttons -->
    <div class="w-100 d-flex justify-content-end gap-2 mt-3 pt-2 border-top no-print">
        <button class="btn btn-sm btn-primary bg-gradient px-3 rounded-pill shadow-sm" type="button" id="print_receipt">
            <i class="fa fa-print me-1"></i> Print Thermal Receipt
        </button>
        <a href="PDF.php?MST_ID=<?php echo urlencode($transaction_id); ?>" id="download_pdf_btn" target="_blank" class="btn btn-sm btn-success bg-gradient px-3 rounded-pill shadow-sm">
            <i class="fa fa-download me-1"></i> Download PDF
        </a>
        <button class="btn btn-sm btn-secondary px-4 rounded-pill shadow-sm" type="button" data-bs-dismiss="modal" id="btn_close_receipt">Close</button>
    </div>
</div>

<script>
    $(function(){
        // Refresh page on modal close
        $('#btn_close_receipt').click(function(){
            location.reload();
        });

        // Trigger reload when PDF download is triggered
        $('#download_pdf_btn').click(function(){
            setTimeout(function(){
                location.reload();
            }, 1000);
        });

        // Pop-up frame printing handler
        $('#print_receipt').click(function(){
            var printContents = $('#outprint_receipt').clone();
            
            var printWindow = window.open("", "_blank", "width=400,height=600");
            printWindow.document.write('<html><head><title>Print Receipt</title>');
            
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: "Courier New", Courier, monospace; width: 100%; margin: 0; padding: 5px; font-size: 11px; }');
            printWindow.document.write('.text-center { text-align: center; }');
            printWindow.document.write('.text-end { text-align: right; }');
            printWindow.document.write('.fw-bold { font-weight: bold; }');
            printWindow.document.write('.dotted-line { border-bottom: 1px dashed #000; margin: 4px 0; }');
            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }');
            printWindow.document.write('th, td { padding: 2px 0; vertical-align: top; }');
            printWindow.document.write('.d-flex { display: flex; justify-content: space-between; }');
            printWindow.document.write('</style>');
            
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContents.html());
            printWindow.document.write('</body></html>');
            printWindow.document.close();

            setTimeout(function(){
                printWindow.focus();
                printWindow.print();
                setTimeout(function(){
                    printWindow.close();
                    location.reload();
                }, 300);
            }, 250);
        });
    });
</script>