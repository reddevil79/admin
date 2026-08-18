<?php 
$dfrom = isset($_GET['date_from']) && !empty($_GET['date_from']) ? date("Y-m-d", strtotime($_GET['date_from'])) : date("Y-m-d", strtotime("-1 week"));
$dto = isset($_GET['date_to']) && !empty($_GET['date_to']) ? date("Y-m-d", strtotime($_GET['date_to'])) : date("Y-m-d");

$user_id = $_SESSION['user_id'] ?? 0;
$user_type = $_SESSION['type'] ?? 0;
?>

<!-- html2pdf library for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="content py-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold text-dark mb-0"><span class="fa fa-chart-line me-2 text-primary"></span>Sales Report</h3>
                <p class="text-muted small mb-0">Analyze and review transaction revenues across custom date intervals.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger bg-gradient px-3 py-2 rounded-pill shadow-sm fw-semibold" id="export_pdf" type="button">
                    <i class="fa fa-file-pdf me-1"></i> Save as PDF
                </button>
                <button class="btn btn-outline-secondary bg-gradient px-3 py-2 rounded-pill shadow-sm fw-semibold" id="print" type="button">
                    <i class="fa fa-print me-1"></i> Print Report
                </button>
            </div>
        </div>

        <div class="card-body p-4 bg-light">
            <!-- Filter Section Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa fa-filter text-primary me-2"></i>Filter Options</h5>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="date_from" class="form-label small fw-semibold text-muted">Date From</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($dfrom); ?>" class="form-control rounded-3 bg-light border-0 py-2">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label small fw-semibold text-muted">Date To</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($dto); ?>" class="form-control rounded-3 bg-light border-0 py-2">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary bg-gradient w-100 rounded-3 py-2 shadow-sm fw-semibold" id="filter" type="button">
                                <i class="fa fa-search me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Results Table Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div id="outprint">
                        <div class="text-center mb-3 d-none print-header">
                            <h3 class="fw-bold mb-1">Donut Pasal</h3>
                            <p class="text-muted mb-0">Sales Report: <strong><?php echo date('M d, Y', strtotime($dfrom)) . ($dfrom != $dto ? ' to ' . date('M d, Y', strtotime($dto)) : ''); ?></strong></p>
                            <hr>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="report-table">
                                <thead class="table-dark text-uppercase fs-7">
                                    <tr>
                                        <th class="text-center py-3 px-2">No</th>
                                        <th class="py-3 px-2">Date & Time</th>
                                        <th class="py-3 px-2">Receipt No</th>
                                        <th class="text-end py-3 px-2">Items</th>
                                        <th class="text-end py-3 px-2">Subtotal</th>
                                        <th class="text-end py-3 px-2">Discount</th>
                                        <th class="text-end py-3 px-2">Final Payable</th>
                                        <th class="py-3 px-2">Processed By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $where = "WHERE DATE(t.date_added) BETWEEN ? AND ? ";
                                    $types = "ss";
                                    $params = [$dfrom, $dto];

                                    if ($user_type != 1) {
                                        $where .= " AND t.user_id = ? ";
                                        $types .= "i";
                                        $params[] = $user_id;
                                    }

                                    $query_str = "SELECT t.*, COALESCE(u.username, u.email, 'N/A') as processed_by, COUNT(ti.transaction_id) as item_count 
                                                  FROM `transaction_list` t 
                                                  LEFT JOIN `user_list` u ON t.user_id = u.user_id 
                                                  LEFT JOIN `transaction_items` ti ON t.transaction_id = ti.transaction_id 
                                                  {$where} 
                                                  GROUP BY t.transaction_id 
                                                  ORDER BY UNIX_TIMESTAMP(t.date_added) ASC";

                                    $stmt = $conn->prepare($query_str);
                                    if ($stmt) {
                                        $stmt->bind_param($types, ...$params);
                                        $stmt->execute();
                                        $qry = $stmt->get_result();

                                        $i = 1;
                                        $sum_subtotal = 0;
                                        $sum_discount = 0;
                                        $sum_final = 0;
                                        
                                        if ($qry && $qry->num_rows > 0):
                                            while ($row = $qry->fetch_assoc()):
                                                $subtotal = floatval($row['total']);
                                                $disc_percent = isset($row['discount_percent']) ? floatval($row['discount_percent']) : 0;
                                                $disc_amount = ($subtotal * $disc_percent) / 100;
                                                $final_amt = isset($row['final_amount']) ? floatval($row['final_amount']) : ($subtotal - $disc_amount);

                                                $sum_subtotal += $subtotal;
                                                $sum_discount += $disc_amount;
                                                $sum_final += $final_amt;
                                    ?>
                                    <tr>
                                        <td class="text-center py-3 px-2 fw-semibold text-muted"><?php echo $i++; ?></td>
                                        <td class="py-3 px-2 font-monospace text-secondary small"><?php echo date("Y-m-d H:i", strtotime($row['date_added'])); ?></td>
                                        <td class="py-3 px-2">
                                            <a href="javascript:void(0)" class="view_data fw-bold text-primary text-decoration-none" data-id="<?php echo $row['transaction_id']; ?>">
                                                <i class="fa fa-receipt me-1"></i><?php echo htmlspecialchars($row['receipt_no']); ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-2 text-end">
                                            <span class="badge bg-secondary bg-gradient px-2 py-1"><?php echo number_format($row['item_count']); ?></span>
                                        </td>
                                        <td class="py-3 px-2 text-end font-monospace text-muted">Rs. <?php echo number_format($subtotal, 2); ?></td>
                                        <td class="py-3 px-2 text-end font-monospace text-danger">-Rs. <?php echo number_format($disc_amount, 2); ?> <small class="fs-7">(<?php echo number_format($disc_percent, 1); ?>%)</small></td>
                                        <td class="py-3 px-2 text-end font-monospace fw-bold text-success">Rs. <?php echo number_format($final_amt, 2); ?></td>
                                        <td class="py-3 px-2 text-secondary"><?php echo htmlspecialchars($row['processed_by']); ?></td>
                                    </tr>
                                    <?php 
                                            endwhile; 
                                        else: 
                                    ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted fst-italic">No transactions listed in selected date range.</td>
                                    </tr>
                                    <?php 
                                        endif;
                                        $stmt->close();
                                    } 
                                    ?>
                                </tbody>
                                <?php if (isset($qry) && $qry && $qry->num_rows > 0): ?>
                                <tfoot class="table-light border-top fw-bold">
                                    <tr>
                                        <td colspan="4" class="text-end py-3 px-2">Grand Totals:</td>
                                        <td class="text-end py-3 px-2 font-monospace text-dark">Rs. <?php echo number_format($sum_subtotal, 2); ?></td>
                                        <td class="text-end py-3 px-2 font-monospace text-danger">-Rs. <?php echo number_format($sum_discount, 2); ?></td>
                                        <td class="text-end py-3 px-2 font-monospace text-primary fs-6">Rs. <?php echo number_format($sum_final, 2); ?></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function(){
        $('.view_data').click(function(){
            uni_modal('Receipt Details', "view_receipt.php?view_only=true&id=" + $(this).attr('data-id'), 'modal-lg')
        });

        // Date Filtering Logic
        $('#filter').click(function(){
            var dfrom = $('#date_from').val();
            var dto = $('#date_to').val();
            
            if(!dfrom || !dto){
                alert("Please select both 'Date From' and 'Date To'.");
                return;
            }

            var url = new URL(window.location.href);
            url.searchParams.set("date_from", dfrom);
            url.searchParams.set("date_to", dto);
            window.location.href = url.toString();
        });

        // Print Report Logic
        $('#print').click(function(){
            var printWindow = window.open('', '_blank');
            if(!printWindow){
                alert("Please allow pop-ups for this site to print reports.");
                return;
            }
            
            var date_range = ('<?php echo $dfrom; ?>' === '<?php echo $dto; ?>') 
                ? "<?php echo date('M d, Y', strtotime($dfrom)); ?>"
                : "<?php echo date('M d, Y', strtotime($dfrom)) . ' to ' . date('M d, Y', strtotime($dto)); ?>";

            var content = $('#outprint').clone();
            content.find('.print-header').removeClass('d-none');
            content.find('a').contents().unwrap(); // Remove hyperlinks for clean print

            var htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Sales Report - <?php echo date('Ymd'); ?></title>
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
                    <style>
                        body { padding: 20px; color: #000; background: #fff; font-size: 12px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                        th, td { border: 1px solid #dee2e6 !important; padding: 6px 8px !important; }
                        th { background-color: #212529 !important; color: white !important; -webkit-print-color-adjust: exact; }
                        .badge { border: 1px solid #6c757d; color: #000 !important; background: transparent !important; }
                    </style>
                </head>
                <body>
                    ${content.html()}
                </body>
                </html>
            `;

            printWindow.document.write(htmlContent);
            printWindow.document.close();

            setTimeout(function(){
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            }, 500);
        });

        // Save/Download PDF Logic
        $('#export_pdf').click(function(){
            var element = document.createElement('div');
            var content = $('#outprint').clone();
            
            content.find('.print-header').removeClass('d-none');
            content.find('a').contents().unwrap();
            
            element.innerHTML = `
                <div style="padding: 15px; font-size: 11px;">
                    <style>
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .text-end { text-align: right; }
                        .text-center { text-align: center; }
                    </style>
                    ${content.html()}
                </div>
            `;

            var opt = {
                margin:       [0.4, 0.4, 0.4, 0.4],
                filename:     'Sales_Report_<?php echo $dfrom; ?>_to_<?php echo $dto; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
            };

            html2pdf().set(opt).from(element).save();
        });
    });
</script>