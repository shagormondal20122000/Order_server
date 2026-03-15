<?php
use Core\Product;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN)) {
    Helpers::redirect('/login');
}

$productModel = new Product();
$error = '';
$success = '';

// Handle Category Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = Helpers::sanitize($_POST['name']);
    $slug = Helpers::generateSlug($name);
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

    if ($productModel->createCategory($name, $slug, $parent_id)) {
        $success = "Category created successfully!";
    } else {
        $error = "Failed to create category. Slug might already exist.";
    }
}

$categories = $productModel->getCategories();

$page_title = 'Manage Categories';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5>Add New Category</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="add_category" value="1">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Parent Category (Optional)</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None (Top Level)</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark">Create Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5>Category List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Parent ID</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?php echo $cat['id']; ?></td>
                                        <td><?php echo $cat['name']; ?></td>
                                        <td><?php echo $cat['slug']; ?></td>
                                        <td><?php echo $cat['parent_id'] ? $cat['parent_id'] : '-'; ?></td>
                                        <td><span class="badge bg-success"><?php echo ucfirst($cat['status']); ?></span></td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <a href="#" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($categories)): ?>
                                    <tr><td colspan="6" class="text-center">No categories found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/admin_footer.php'; ?>
