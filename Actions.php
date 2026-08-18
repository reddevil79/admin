<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php-errors.log');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('DBConnection.php');

class Actions extends DBConnection
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    private function return_json_response($data)
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        return json_encode($data);
    }

    public function login()
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Please enter both username and password.'
            ]);
        }

        $stmt = $this->db->prepare("SELECT * FROM `user_list` WHERE `username` = ? LIMIT 1");
        if (!$stmt) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Database query failed: ' . $this->db->error
            ]);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $hash = $user['password'];

            $verified = false;
            if (password_verify($password, $hash)) {
                $verified = true;
            } elseif (md5($password) === $hash || $password === $hash) {
                $verified = true;
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $u_stmt = $this->db->prepare("UPDATE `user_list` SET `password` = ? WHERE `user_id` = ?");
                if ($u_stmt) {
                    $u_stmt->bind_param("si", $newHash, $user['user_id']);
                    $u_stmt->execute();
                    $u_stmt->close();
                }
            }

            if ($verified) {
                foreach ($user as $k => $v) {
                    if (!is_numeric($k)) {
                        $_SESSION[$k] = $v;
                    }
                }
                $stmt->close();
                return $this->return_json_response([
                    'status' => 'success',
                    'msg' => 'Login successfully.'
                ]);
            }
        }

        $stmt->close();
        return $this->return_json_response([
            'status' => 'failed',
            'msg' => 'Invalid username or password.'
        ]);
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header("Location: ./login.php");
        exit;
    }

    public function save_user()
    {
        $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $cpassword = $_POST['cpassword'] ?? '';

        if (empty($email) || empty($username)) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Email and Username are required.'
            ]);
        }

        if (empty($id) && empty($password)) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Password is required for new users.'
            ]);
        }

        if (!empty($password) && $password !== $cpassword) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Passwords do not match.'
            ]);
        }

        $stmt = $this->db->prepare("SELECT COUNT(`user_id`) FROM `user_list` WHERE `email` = ? AND `user_id` != ?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Email already exists.'
            ]);
        }

        $stmt = $this->db->prepare("SELECT COUNT(`user_id`) FROM `user_list` WHERE `username` = ? AND `user_id` != ?");
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Username already exists.'
            ]);
        }

        if (empty($id)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("INSERT INTO `user_list` (`email`, `username`, `password`) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $username, $hashed);
        } else {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $this->db->prepare("UPDATE `user_list` SET `email` = ?, `username` = ?, `password` = ? WHERE `user_id` = ?");
                $stmt->bind_param("sssi", $email, $username, $hashed, $id);
            } else {
                $stmt = $this->db->prepare("UPDATE `user_list` SET `email` = ?, `username` = ? WHERE `user_id` = ?");
                $stmt->bind_param("ssi", $email, $username, $id);
            }
        }

        if ($stmt->execute()) {
            $stmt->close();
            return $this->return_json_response([
                'status' => 'success',
                'msg' => empty($id) ? 'New User successfully saved.' : 'User Details successfully updated.'
            ]);
        } else {
            $err = $this->db->error;
            $stmt->close();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Saving User Details Failed: ' . $err
            ]);
        }
    }

    public function delete_user()
    {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Invalid User ID.'
            ]);
        }

        $stmt = $this->db->prepare("DELETE FROM `user_list` WHERE `user_id` = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            return $this->return_json_response(['status' => 'success', 'msg' => 'User successfully deleted.']);
        } else {
            $err = $this->db->error;
            $stmt->close();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Error: ' . $err
            ]);
        }
    }

    public function update_credentials()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'User session expired. Please login again.'
            ]);
        }

        $user_id = $_SESSION['user_id'];
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $cpassword = $_POST['cpassword'] ?? '';

        if (empty($email) || empty($username)) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Email and Username are required.'
            ]);
        }

        $stmt = $this->db->prepare("SELECT `password` FROM `user_list` WHERE `user_id` = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if (!$res || $res->num_rows === 0) {
            $stmt->close();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'User not found.'
            ]);
        }
        $current = $res->fetch_assoc();
        $stmt->close();

        if (!empty($cpassword)) {
            $valid_old = password_verify($cpassword, $current['password']) || md5($cpassword) === $current['password'] || $cpassword === $current['password'];
            if (!$valid_old) {
                return $this->return_json_response([
                    'status' => 'failed',
                    'msg' => 'Current password is incorrect.'
                ]);
            }
        }

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE `user_list` SET `email` = ?, `username` = ?, `password` = ? WHERE `user_id` = ?");
            $stmt->bind_param("sssi", $email, $username, $hashed, $user_id);
        } else {
            $stmt = $this->db->prepare("UPDATE `user_list` SET `email` = ?, `username` = ? WHERE `user_id` = ?");
            $stmt->bind_param("ssi", $email, $username, $user_id);
        }

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;
            return $this->return_json_response([
                'status' => 'success',
                'msg' => 'Credentials successfully updated.'
            ]);
        } else {
            $err = $this->db->error;
            $stmt->close();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Updating Credentials Failed: ' . $err
            ]);
        }
    }

    public function save_category()
    {
        $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

        if (empty($name)) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Category name is required.'
            ]);
        }

        $stmt = $this->db->prepare("SELECT COUNT(`category_id`) FROM `category_list` WHERE `name` = ? AND `category_id` != ? AND `delete_flag` = 0");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Category already exists.'
            ]);
        }

        if (empty($id)) {
            $stmt = $this->db->prepare("INSERT INTO `category_list` (`name`, `description`, `status`) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $description, $status);
        } else {
            $stmt = $this->db->prepare("UPDATE `category_list` SET `name` = ?, `description` = ?, `status` = ? WHERE `category_id` = ?");
            $stmt->bind_param("ssii", $name, $description, $status, $id);
        }

        if ($stmt->execute()) {
            $stmt->close();
            return $this->return_json_response([
                'status' => 'success',
                'msg' => empty($id) ? 'Category successfully saved.' : 'Category successfully updated.'
            ]);
        } else {
            $err = $this->db->error;
            $stmt->close();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => empty($id) ? 'Saving New Category Failed.' : 'Updating Category Failed.',
                'error' => $err
            ]);
        }
    }

    public function delete_category()
    {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Invalid Category ID.'
            ]);
        }

        $chk_stmt = $this->db->prepare("SELECT COUNT(`product_id`) FROM `product_list` WHERE `category_id` = ? AND `delete_flag` = 0");
        if ($chk_stmt) {
            $chk_stmt->bind_param("i", $id);
            $chk_stmt->execute();
            $chk_stmt->bind_result($product_count);
            $chk_stmt->fetch();
            $chk_stmt->close();

            if ($product_count > 0) {
                return $this->return_json_response([
                    'status' => 'failed',
                    'msg' => 'Action denied: This category contains active products and cannot be deleted.'
                ]);
            }
        }

        $stmt = $this->db->prepare("UPDATE `category_list` SET `delete_flag` = 1 WHERE `category_id` = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            return $this->return_json_response([
                'status' => 'success',
                'msg' => 'Category has been deleted successfully.'
            ]);
        } else {
            $err = $this->db->error;
            $stmt->close();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Error: ' . $err
            ]);
        }
    }

    public function save_product()
    {
        try {
            $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $product_code = trim($_POST['product_code'] ?? '');
            $category_id = intval($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $alert_restock = floatval($_POST['alert_restock'] ?? 0);
            $status = intval($_POST['status'] ?? 1);

            $stock = 0;
            if (isset($_POST['stock'])) {
                $stock = floatval($_POST['stock']);
            } elseif (isset($_POST['quantity'])) {
                $stock = floatval($_POST['quantity']);
            } elseif (isset($_POST['total_stock'])) {
                $stock = floatval($_POST['total_stock']);
            }

            // Target physical directory for file storage
            $upload_dir = 'images/products/';
            $absolute_upload_path = __DIR__ . '/' . $upload_dir;
            if (!file_exists($absolute_upload_path)) {
                @mkdir($absolute_upload_path, 0777, true);
            }

            $image = '';
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $filename = $_FILES['image']['name'];
                $temp_file = $_FILES['image']['tmp_name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                if (!in_array($ext, $allowed_extensions)) {
                    return $this->return_json_response([
                        'status' => 'failed',
                        'msg' => 'Invalid file format. Only JPG, PNG, WEBP, and GIF images are allowed.'
                    ]);
                }

                $image_filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
                $destination = $absolute_upload_path . $image_filename;

                if (@move_uploaded_file($temp_file, $destination)) {
                    $image = 'images/products/' . $image_filename;
                } else {
                    return $this->return_json_response([
                        'status' => 'failed',
                        'msg' => 'Failed to move uploaded image file. Please verify folder write permissions.'
                    ]);
                }
            } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                return $this->return_json_response([
                    'status' => 'failed',
                    'msg' => 'Image upload error code: ' . $_FILES['image']['error']
                ]);
            } elseif (!empty($_POST['current_image'])) {
                $image = trim($_POST['current_image']);
            }

            // Corrected 10-character bind string: "sissddsdii"
            // s: product_code, i: category_id, s: name, s: description, d: price, d: stock, s: image, d: alert_restock, i: status, i: id
            if (empty($id)) {
                $stmt = $this->db->prepare("INSERT INTO `product_list` (`product_code`, `category_id`, `name`, `description`, `price`, `stock`, `image`, `alert_restock`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    return $this->return_json_response(['status' => 'failed', 'msg' => 'Prepare failed: ' . $this->db->error]);
                }
                $stmt->bind_param("sissddsdi", $product_code, $category_id, $name, $description, $price, $stock, $image, $alert_restock, $status);
            } else {
                $stmt = $this->db->prepare("UPDATE `product_list` SET `product_code` = ?, `category_id` = ?, `name` = ?, `description` = ?, `price` = ?, `stock` = ?, `image` = ?, `alert_restock` = ?, `status` = ?, `date_updated` = NOW() WHERE `product_id` = ?");
                if (!$stmt) {
                    return $this->return_json_response(['status' => 'failed', 'msg' => 'Prepare failed: ' . $this->db->error]);
                }
                $stmt->bind_param("sissddsdii", $product_code, $category_id, $name, $description, $price, $stock, $image, $alert_restock, $status, $id);
            }

            if ($stmt->execute()) {
                $stmt->close();
                return $this->return_json_response([
                    'status' => 'success',
                    'msg' => 'Product saved successfully.'
                ]);
            } else {
                $err = $stmt->error;
                $stmt->close();
                return $this->return_json_response([
                    'status' => 'failed',
                    'msg' => 'Database error: ' . $err
                ]);
            }
        } catch (Exception $e) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    public function delete_product()
    {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Invalid Product ID.'
            ]);
        }

        $stmt = $this->db->prepare("UPDATE `product_list` SET `delete_flag` = 1 WHERE `product_id` = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            return $this->return_json_response([
                'status' => 'success',
                'msg' => 'Product has been deleted successfully.'
            ]);
        } else {
            $err = $this->db->error;
            $stmt->close();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Error: ' . $err
            ]);
        }
    }

    public function save_transaction()
    {
        $id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
        $user_id = $_SESSION['user_id'] ?? 0;

        // --- Capture every financial field submitted by the POS form ---
        // (previously only total/tendered_amount/change were read, so
        // sub_total / discount_type / discount_amount / discount_percent
        // were silently discarded and never reached the database)
        $sub_total       = floatval($_POST['sub_total'] ?? 0);
        $discount_type   = ($_POST['discount_type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $discount_amount = floatval($_POST['discount_amount'] ?? 0);
        $discount_percent = floatval($_POST['discount_percent'] ?? 0);
        $tendered_amount = floatval($_POST['tendered_amount'] ?? ($_POST['tendered'] ?? 0));

        $product_ids = $_POST['product_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price'] ?? [];

        // --- Server-side integrity pass ---
        // Never trust the client's arithmetic for money that is about to be
        // written to the ledger. Clamp the discount to the subtotal, then
        // derive payable and change from *these* validated values only.
        // This is the single source of truth the receipt will later read
        // back verbatim (it must never recompute).
        if ($sub_total < 0) {
            $sub_total = 0;
        }
        if ($discount_type === 'percent') {
            $discount_percent = max(0, min(100, $discount_percent));
            $discount_amount = round(($sub_total * $discount_percent) / 100, 2);
        } else {
            $discount_amount = max(0, min($sub_total, $discount_amount));
            $discount_percent = $sub_total > 0 ? round(($discount_amount / $sub_total) * 100, 2) : 0;
        }

        // `total` is this system's payable-amount column (subtotal - discount).
        $total = round($sub_total - $discount_amount, 2);
        if ($total < 0) {
            $total = 0;
        }

        // Change must always be tendered - payable, never tendered - subtotal.
        $change = round($tendered_amount - $total, 2);
        if ($change < 0) {
            $change = 0;
        }

        $this->db->begin_transaction();

        try {
            $receipt_no = (string)time();
            $i = 0;
            while (true) {
                $stmt = $this->db->prepare("SELECT COUNT(`transaction_id`) FROM `transaction_list` WHERE `receipt_no` = ?");
                $stmt->bind_param("s", $receipt_no);
                $stmt->execute();
                $stmt->bind_result($chk);
                $stmt->fetch();
                $stmt->close();

                if ($chk > 0) {
                    $i++;
                    $receipt_no = time() . $i;
                } else {
                    break;
                }
            }

            if (empty($id)) {
                $stmt = $this->db->prepare("INSERT INTO `transaction_list` (`receipt_no`, `user_id`, `sub_total`, `discount_type`, `discount_amount`, `discount_percent`, `total`, `tendered_amount`, `change`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sidsddddd", $receipt_no, $user_id, $sub_total, $discount_type, $discount_amount, $discount_percent, $total, $tendered_amount, $change);
            } else {
                $stmt = $this->db->prepare("UPDATE `transaction_list` SET `sub_total` = ?, `discount_type` = ?, `discount_amount` = ?, `discount_percent` = ?, `total` = ?, `tendered_amount` = ?, `change` = ? WHERE `transaction_id` = ?");
                $stmt->bind_param("dsdddddi", $sub_total, $discount_type, $discount_amount, $discount_percent, $total, $tendered_amount, $change, $id);
            }

            if (!$stmt->execute()) {
                throw new Exception("Transaction headers failed: " . $this->db->error);
            }

            $tid = empty($id) ? $this->db->insert_id : $id;
            $stmt->close();

            $del_stmt = $this->db->prepare("DELETE FROM `transaction_items` WHERE `transaction_id` = ?");
            $del_stmt->bind_param("i", $tid);
            $del_stmt->execute();
            $del_stmt->close();

            if (is_array($product_ids) && count($product_ids) > 0) {
                $item_stmt = $this->db->prepare("INSERT INTO `transaction_items` (`transaction_id`, `product_id`, `quantity`, `price`) VALUES (?, ?, ?, ?)");
                $stock_stmt = $this->db->prepare("UPDATE `product_list` SET `stock` = `stock` - ? WHERE `product_id` = ?");

                foreach ($product_ids as $k => $pid) {
                    $p_id = intval($pid);
                    $qty = floatval($quantities[$k] ?? 0);
                    $prc = floatval($prices[$k] ?? 0);

                    $item_stmt->bind_param("iidd", $tid, $p_id, $qty, $prc);
                    $item_stmt->execute();

                    if (empty($id)) {
                        $stock_stmt->bind_param("di", $qty, $p_id);
                        $stock_stmt->execute();
                    }
                }
                $item_stmt->close();
                $stock_stmt->close();
            }

            $this->db->commit();

            return $this->return_json_response([
                'status' => 'success',
                'msg' => empty($id) ? 'Transaction successfully saved.' : 'Transaction successfully updated.',
                'transaction_id' => $tid
            ]);

        } catch (Exception $e) {
            $this->db->rollback();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => empty($id) ? 'Saving New Transaction Failed.' : 'Updating Transaction Failed.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function delete_transaction()
    {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Invalid Transaction ID.'
            ]);
        }

        $stmt = $this->db->prepare("DELETE FROM `transaction_list` WHERE `transaction_id` = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            return $this->return_json_response(['status' => 'success', 'msg' => 'Transaction successfully deleted.']);
        } else {
            $err = $this->db->error;
            $stmt->close();
            return $this->return_json_response([
                'status' => 'failed',
                'msg' => 'Error: ' . $err
            ]);
        }
    }
}

$a = $_GET['a'] ?? '';
$action = new Actions();

switch ($a) {
    case 'login':
        echo $action->login();
        break;

    case 'logout':
        echo $action->logout();
        break;

    case 'save_user':
    case 'edit_user':
    case 'update_user':
        echo $action->save_user();
        break;

    case 'delete_user':
        echo $action->delete_user();
        break;

    case 'update_credentials':
        echo $action->update_credentials();
        break;

    case 'save_category':
        echo $action->save_category();
        break;

    case 'delete_category':
        echo $action->delete_category();
        break;

    case 'save_product':
        echo $action->save_product();
        break;

    case 'delete_product':
        echo $action->delete_product();
        break;

    case 'save_transaction':
        echo $action->save_transaction();
        break;

    case 'delete_transaction':
        echo $action->delete_transaction();
        break;

    default:
        echo json_encode(['status' => 'failed', 'msg' => 'Invalid action']);
        break;
}