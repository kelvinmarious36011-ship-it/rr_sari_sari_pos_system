<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$user = currentUser();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $id = intval($_POST['id'] ?? 0);

        if ($name && $price > 0) {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
                $stmt->bind_param("sdi", $name, $price, $stock);
                $stmt->execute();
                $message = "Product added successfully!";
            } else {
                $stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=? WHERE id=?");
                $stmt->bind_param("sdii", $name, $price, $stock, $id);
                $stmt->execute();
                $message = "Product updated successfully!";
            }
            $stmt->close();
            $messageType = 'success';
        } else {
            $message = "Please enter a valid name and price.";
            $messageType = 'error';
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $message = "Product deleted.";
        $messageType = 'success';
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM products WHERE id = $id");
    $editProduct = $res->fetch_assoc();
}

$products = $conn->query("SELECT * FROM products ORDER BY name ASC");
$lowStockCount = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock <= 5")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products •rr_sari_sari_pos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand">rr_sari_sari_pos <span>• POS</span></div>
        <div class="nav-links">
            <a href="index.php">POS</a>
            <a href="products.php" class="active">Products <?= $lowStockCount > 0 ? "<span style='background:#dc2626;color:white;font-size:0.7rem;padding:1px 6px;border-radius:10px;margin-left:2px;'>$lowStockCount</span>" : '' ?></a>
            <a href="transactions.php">Sales Log</a>
            <a href="daily.php">Daily Report</a>
            <a href="logout.php" style="opacity:0.85;">Logout (<?= htmlspecialchars($user['full_name']) ?>)</a>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">Manage Products</h1>

        <?php if ($lowStockCount > 0): ?>
            <div class="alert alert-error">
                ⚠ <strong><?= $lowStockCount ?> product(s)</strong> have low stock (5 or less). Please restock soon.
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1.25rem;" class="products-layout">
            <div class="card">
                <div class="card-header">
                    <h2><?= $editProduct ? 'Edit Product' : 'Add New Product' ?></h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $editProduct ? 'edit' : 'add' ?>">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" required
                               value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>"
                               placeholder="e.g. Lucky Me Pancit Canton">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (₱)</label>
                            <input type="number" name="price" step="0.01" min="0.01" required
                                   value="<?= $editProduct['price'] ?? '' ?>" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Stock</label>
                            <input type="number" name="stock" min="0" value="<?= $editProduct['stock'] ?? 0 ?>">
                        </div>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <?= $editProduct ? 'Update Product' : 'Add Product' ?>
                        </button>
                        <?php if ($editProduct): ?>
                            <a href="products.php" class="btn btn-outline">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>All Products (<?= $products->num_rows ?>)</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $products->fetch_assoc()): ?>
                                <?php $isLow = $p['stock'] <= 5; ?>
                                <tr style="<?= $isLow ? 'background:#fef2f2;' : '' ?>">
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                    <td>₱<?= number_format($p['price'], 2) ?></td>
                                    <td style="<?= $isLow ? 'color:#dc2626;font-weight:700;' : '' ?>">
                                        <?= $p['stock'] ?><?= $isLow ? ' ⚠ Low' : '' ?>
                                    </td>
                                    <td>
                                        <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 800px) {
            .products-layout { grid-template-columns: 1fr !important; }
        }
    </style>
</body>
</html>