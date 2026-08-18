<?php 
require_once('DBConnection.php');
?>

<div class="content py-4">
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-4">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h3 class="fw-bold text-dark mb-1">Welcome to Bakery Management System</h3>
          <p class="text-muted small mb-0">Here is an overview of your store's inventory and daily sales performance.</p>
        </div>
      </div>
      <hr class="text-muted opacity-25">

      <!-- Metric Cards Grid -->
      <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <!-- Categories Card -->
        <div class="col">
          <a href="./?page=maintenance" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary bg-gradient text-white transition-hover">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="text-uppercase fw-semibold tracking-wider opacity-75 mb-1">Categories</h6>
                    <h2 class="fw-bold mb-0">
                      <?php 
                      $cat_res = $conn->query("SELECT count(category_id) as `count` FROM `category_list` WHERE delete_flag = 0");
                      $category = $cat_res ? $cat_res->fetch_array()['count'] : 0;
                      echo $category > 0 ? format_num($category) : 0;
                      ?>
                    </h2>
                  </div>
                  <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                    <span class="fa fa-th-list fs-2"></span>
                  </div>
                </div>
              </div>
            </div>
          </a>
        </div>

        <!-- Products Card -->
        <div class="col">
          <a href="./?page=products" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-success bg-gradient text-white transition-hover">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="text-uppercase fw-semibold tracking-wider opacity-75 mb-1">Products</h6>
                    <h2 class="fw-bold mb-0">
                      <?php 
                      $prod_res = $conn->query("SELECT count(product_id) as `count` FROM `product_list` WHERE delete_flag = 0");
                      $product = $prod_res ? $prod_res->fetch_array()['count'] : 0;
                      echo $product > 0 ? format_num($product) : 0;
                      ?>
                    </h2>
                  </div>
                  <div class="p-3 bg-white bg-opacity-25 rounded-circle">
                    <span class="fas fa-shopping-bag fs-2"></span>
                  </div>
                </div>
              </div>
            </div>
          </a>
        </div>

        <!-- Today's Sales Card -->
        <div class="col">
          <a href="./?page=sales_report" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-warning bg-gradient text-dark transition-hover">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <h6 class="text-uppercase fw-semibold tracking-wider opacity-75 mb-1">Today's Sales</h6>
                    <h2 class="fw-bold mb-0">
                      <?php 
                      $sales_res = $conn->query("SELECT sum(total) as `total` FROM `transaction_list` WHERE date(date_added) = date(CURRENT_TIMESTAMP)");
                      $sales = $sales_res ? $sales_res->fetch_array()[0] : 0;
                      echo $sales > 0 ? "Rs. " . format_num($sales) : "Rs. 0";
                      ?>
                    </h2>
                  </div>
                  <div class="p-3 bg-dark bg-opacity-10 rounded-circle">
                    <span class="fa fa-coins fs-2"></span>
                  </div>
                </div>
              </div>
            </div>
          </a>
        </div>
      </div>

      <hr class="text-muted opacity-25 my-4">

      <!-- Available Stock Table Section -->
      <div class="container-fluid px-0">
        <div class="row">
          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h4 class="fw-bold text-dark mb-0"><span class="fa fa-boxes me-2 text-primary"></span>Stock Available</h4>
            </div>
            
            <div class="table-responsive">
              <table class="table table-hover align-middle border" id="inventory">
                <thead class="table-dark text-uppercase fs-7">
                  <tr>
                    <th class="py-3 px-3">Category</th>
                    <th class="py-3 px-3">Code</th>
                    <th class="py-3 px-3 text-center">Image</th>
                    <th class="py-3 px-3">Name</th>
                    <th class="py-3 px-3 text-center">Remaining Stock</th>
                    <th class="py-3 px-3 text-center">Restock Alert</th>
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

                  if ($qry):
                      while ($row = $qry->fetch_assoc()):
                          $remaining_stock = floatval($row['stock'] ?? 0);
                          $alert_restock = floatval($row['alert_restock'] ?? 0);

                          // Updated to match Actions.php path format: images/products/
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
                  <tr>
                    <td class="py-3 px-3 fw-semibold text-secondary"><?php echo htmlspecialchars($row['cname']); ?></td>
                    <td class="py-3 px-3 font-monospace"><?php echo htmlspecialchars($row['product_code']); ?></td>
                    <td class="py-3 px-3 text-center">
                      <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="img-thumbnail rounded-3 shadow-sm" style="height: 45px; width: 45px; object-fit: cover;">
                    </td>
                    <td class="py-3 px-3 fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="py-3 px-3 text-center">
                      <?php if ($remaining_stock <= $alert_restock):?>
                          <span class="badge bg-gradient px-3 py-2 rounded-pill fs-7 shadow-sm text-white" style="background-color: #ff0000;"">
                            <i class="fa fa-exclamation-triangle me-1"></i> <?php echo number_format($remaining_stock); ?> (Low Stock)
                          </span>
                      <?php else: ?>
                          <span class="badge bg-success bg-gradient px-3 py-2 rounded-pill fs-7 shadow-sm">
                            <?php echo number_format($remaining_stock); ?>
                          </span>
                      <?php endif; ?>
                    </td>
                    <td class="py-3 px-3 text-center text-muted fw-semibold">
                      <?php echo number_format($alert_restock); ?>
                    </td>
                  </tr>
                  <?php 
                      endwhile;
                  endif;
                  ?>
                </tbody>
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
        if($.fn.DataTable.isDataTable('#inventory')) {
            $('#inventory').DataTable().destroy();
        }
        $('#inventory').DataTable({
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [2] } 
            ]
        });
    });
</script>

<style>
    .transition-hover {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .transition-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
</style>