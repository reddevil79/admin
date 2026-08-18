<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once("DBConnection.php");              
require_once('tcpdf/tcpdf.php');

$transaction_id = isset($_GET['MST_ID']) && is_numeric($_GET['MST_ID']) ? intval($_GET['MST_ID']) : 0;

if ($transaction_id <= 0) {
    die("Invalid Transaction Request.");
}

// Fetch transaction master details including discount columns using Prepared Statement
$stmt = $conn->prepare("SELECT transaction_id, receipt_no, date_added, total, discount_percent, final_amount, tendered_amount, change FROM transaction_list WHERE transaction_id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$inv_mst_results = $stmt->get_result();

if ($inv_mst_results && $inv_mst_results->num_rows > 0) {
    $inv_mst_data_row = $inv_mst_results->fetch_assoc();
    $stmt->close();

    // Fallback support if final_amount/discount_percent columns don't exist in older legacy rows
    $subtotal = isset($inv_mst_data_row['total']) ? (float)$inv_mst_data_row['total'] : 0;
    $disc_percent = isset($inv_mst_data_row['discount_percent']) ? (float)$inv_mst_data_row['discount_percent'] : 0;
    $final_amt = isset($inv_mst_data_row['final_amount']) ? (float)$inv_mst_data_row['final_amount'] : $subtotal;
    $discount_amount = ($subtotal * $disc_percent) / 100;

    // Initialize TCPDF Document
    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle("Receipt_" . $inv_mst_data_row['receipt_no']);
    $pdf->SetMargins(10, 10, 10);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(TRUE, 10);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();

    $content = '
    <style type="text/css">
        body {
            font-size: 11px;
            line-height: 18px;
            font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .border-bottom { border-bottom: 1px solid #ddd; }
        .heading { background-color: #f8f9fa; font-weight: bold; }
    </style>    
    <table cellpadding="4" cellspacing="0" style="width:100%; border:1px solid #ccc;">
        <tr>
            <td colspan="3" class="text-center">
                <b style="font-size:14px;">Bakery Management System</b><br/>
                <span>CONTACT: (555) 019-2834</span>
            </td>
        </tr>
        <tr><td colspan="3" class="border-bottom"></td></tr>
        <tr>
            <td class="text-left"><b>BILL NO:</b> ' . htmlspecialchars($inv_mst_data_row['receipt_no']) . '</td>
            <td colspan="2" class="text-right"><b>DATE:</b> ' . date("Y-m-d H:i", strtotime($inv_mst_data_row['date_added'])) . '</td>
        </tr>
        <tr>
            <td colspan="3" class="text-center" style="font-size: 14px;"><b>OFFICIAL RECEIPT</b></td>
        </tr>
        <tr class="heading">
            <td width="20%" class="text-center">QTY</td>
            <td width="50%" class="text-left">PRODUCT</td>
            <td width="30%" class="text-right">AMOUNT</td>
        </tr>';
    
    // Fetch line items using Prepared Statement
    $item_stmt = $conn->prepare("SELECT i.*, p.name as pname, p.product_code FROM transaction_items i INNER JOIN product_list p ON i.product_id = p.product_id WHERE i.transaction_id = ?");
    $item_stmt->bind_param("i", $transaction_id);
    $item_stmt->execute();
    $inv_det_results = $item_stmt->get_result();

    while ($inv_det_data_row = $inv_det_results->fetch_assoc()) {	
        $qty = (float)$inv_det_data_row['quantity'];
        $price = (float)$inv_det_data_row['price'];
        $amount = $qty * $price;

        $content .= '
        <tr>
            <td class="text-center">' . $qty . '</td>
            <td class="text-left">
                <b>' . htmlspecialchars($inv_det_data_row['pname']) . '</b><br/>
                <small style="color: #6c757d;">Code: ' . htmlspecialchars($inv_det_data_row['product_code']) . ' | Rate: $' . number_format($price, 2) . '</small>
            </td>
            <td class="text-right">$' . number_format($amount, 2) . '</td>
        </tr>';
    }
    $item_stmt->close();

    $content .= '
        <tr><td colspan="3" class="border-bottom"></td></tr>
        <tr>
            <td colspan="2" class="text-right"><b>Subtotal:</b></td>
            <td class="text-right">$' . number_format($subtotal, 2) . '</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right">Discount (' . number_format($disc_percent, 2) . '%):</td>
            <td class="text-right">-$' . number_format($discount_amount, 2) . '</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right"><b>Final Payable:</b></td>
            <td class="text-right"><b>$' . number_format($final_amt, 2) . '</b></td>
        </tr>
        <tr>
            <td colspan="2" class="text-right">Tendered Amount:</td>
            <td class="text-right">$' . number_format((float)$inv_mst_data_row['tendered_amount'], 2) . '</td>
        </tr>
        <tr>
            <td colspan="2" class="text-right">Change:</td>
            <td class="text-right">$' . number_format((float)$inv_mst_data_row['change'], 2) . '</td>
        </tr>
        <tr><td colspan="3" class="border-bottom"></td></tr>
        <tr>
            <td colspan="3" class="text-center"><br/><b>THANK YOU! PLEASE VISIT AGAIN</b></td>
        </tr>
    </table>'; 

    $pdf->writeHTML($content, true, false, true, false, '');

    $file_name = "Receipt_" . $inv_mst_data_row['receipt_no'] . ".pdf";
    
    if (ob_get_length()) {
        ob_end_clean();
    }

    $pdf->Output($file_name, 'D');
} else {
    die("Transaction record not found.");
}
