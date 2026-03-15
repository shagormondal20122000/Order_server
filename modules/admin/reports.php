<?php
use Core\Report;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN)) {
    Helpers::redirect('/login');
}

$reportModel = new Report();

// Default date range (last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-29 days'));

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

$sales_report = $reportModel->getSalesReport($start_date, $end_date);
$product_sales = $reportModel->getProductWiseSales();
$customer_sales = $reportModel->getCustomerWiseSales();

$page_title = 'Sales Reports';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <h3 class="mb-4">Sales & Analytics</h3>

    <!-- Date Range Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="start_date" class="form-label">From</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-auto">
                    <label for="end_date" class="form-label">To</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-auto mt-auto">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Daily Sales Report -->
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Daily Sales Report (<?php echo $start_date; ?> to <?php echo $end_date; ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Total Orders</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_report as $report): ?>
                            <tr>
                                <td><?php echo $report['sale_date']; ?></td>
                                <td><?php echo $report['total_orders']; ?></td>
                                <td><?php echo Helpers::formatCurrency($report['total_revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($sales_report)): ?>
                            <tr><td colspan="3" class="text-center py-3">No sales data found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Product Wise Sales -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Top Selling Products</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_sales as $report): ?>
                                    <tr>
                                        <td><?php echo $report['product_name']; ?></td>
                                        <td><?php echo $report['total_qty_sold']; ?></td>
                                        <td><?php echo Helpers::formatCurrency($report['total_revenue']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Wise Sales -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Top Customers</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer_sales as $report): ?>
                                    <tr>
                                        <td><?php echo $report['customer_name']; ?></td>
                                        <td><?php echo $report['total_orders']; ?></td>
                                        <td><?php echo Helpers::formatCurrency($report['total_spent']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
<?php include 'includes/admin_footer.php'; ?>
