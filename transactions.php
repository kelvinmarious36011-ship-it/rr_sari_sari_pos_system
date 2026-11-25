<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$user = currentUser();

$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(t.transaction_code LIKE ? OR ti.product_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
if ($dateFrom) {
    $where[] = "DATE(t.transaction_date) >= ?";
    $params[] = $dateFrom;
    $types .= 's';
}
if ($dateTo) {
    $where[] = "DATE(t.transaction_date) <= ?";
    $params[] = $dateTo;
    $types .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT t.*, 
               GROUP_CONCAT(CONCAT(ti.quantity, 'x ', ti.product_name) SEPARATOR ', ') AS items_summary
        FROM transactions t
        LEFT JOIN transaction_items ti ON t.id = ti.transaction_id
        $whereSQL
        GROUP BY t.id
        ORDER BY t.transaction_date DESC
        LIMIT 200";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result();

$viewTxn = null;
$viewItems = [];
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $viewTxn = $conn->query("SELECT * FROM transactions WHERE id = $id")->fetch_assoc();
    if ($viewTxn) {
        $viewItems = $conn->query("SELECT * FROM transaction_items WHERE transaction_id = $id")->fetch_all(MYSQLI_ASSOC);
    }
}

$lowStockCount = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock <= 5")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Log • rr_sari_sari_pos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand">rr_sari_sari_pos <span>• POS</span></div>
        <div class="nav-links">
            <a href="index.php">POS</a>
            <a href="products.php">Products <?= $lowStockCount > 0 ? "<span style='background:#dc2626;color:white;font-size:0.7rem;padding:1px 6px;border-radius:10px;margin-left:2px;'>$lowStockCount</span>" : '' ?></a>
            <a href="transactions.php" class="active">Sales Log</a>
            <a href="daily.php">Daily Report</a>
            <a href="logout.php" style="opacity:0.85;">Logout (<?= htmlspecialchars($user['full_name']) ?>)</a>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">Sales Transaction Log</h1>

        <form method="GET" class="filters">
            <div class="form-group">
                <label>Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Code or product...">
            </div>
            <div class="form-group">
                <label>From Date</label>
                <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="form-group">
                <label>To Date</label>
                <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="transactions.php" class="btn btn-outline">Reset</a>
        </form>

        <?php if ($viewTxn): ?>
            <div class="card" style="max-width:480px;margin-bottom:1.5rem;">
                <div class="card-header">
                    <h2>Transaction Detail</h2>
                    <div style="display:flex;gap:0.5rem;">
                        <a href="receipt.php?id=<?= $viewTxn['id'] ?>" target="_blank" class="btn btn-primary btn-sm">Print Receipt</a>
                        <a href="transactions.php" class="btn btn-outline btn-sm">Close</a>
                    </div>
                </div>
                <div class="receipt">
                    <div class="center">
                        <strong>rr_sari_sari_pos</strong><br>
                        Ilaud, Inabanga, Bohol<br>
                        <small><?= $viewTxn['transaction_code'] ?></small>
                    </div>
                    <div class="line"></div>
                    <div><?= date(' d, Y h:i A', strtotime($viewTxn['transaction_date'])) ?></div>
                    <div class="line"></div>
                    <?php foreach ($viewItems as $item): ?>
                        <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                            <span><?= $item['quantity'] ?> x <?= htmlspecialchars($item['product_name']) ?></span>
                            <span>₱<?= number_format($item['subtotal'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="line"></div>
                    <div style="display:flex;justify-content:space-between;font-weight:700;">
                        <span>TOTAL</span>
                        <span>₱<?= number_format($viewTxn['total_amount'], 2) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span>Cash</span>
                        <span>₱<?= number_format($viewTxn['payment_amount'], 2) ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span>Change</span>
                        <span>₱<?= number_format($viewTxn['change_amount'], 2) ?></span>
                    </div>
                    <div class="line"></div>
                    <div class="center"><small>Thank you!</small></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Recent Transactions</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date & Time</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Cash</th>
                            <th>Change</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions->num_rows === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">No transactions found.</td></tr>
                        <?php else: ?>
                            <?php while ($t = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($t['transaction_code']) ?></strong></td>
                                    <td><?= date('M d, Y h:i A', strtotime($t['transaction_date'])) ?></td>
                                    <td style="max-width:280px;font-size:0.9rem;"><?= htmlspecialchars($t['items_summary'] ?? '—') ?></td>
                                    <td><strong>₱<?= number_format($t['total_amount'], 2) ?></strong></td>
                                    <td>₱<?= number_format($t['payment_amount'], 2) ?></td>
                                    <td>₱<?= number_format($t['change_amount'], 2) ?></td>
                                    <td style="white-space:nowrap;">
                                        <a href="transactions.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-sm">View</a>
                                        <a href="receipt.php?id=<?= $t['id'] ?>" target="_blank" class="btn btn-primary btn-sm">Print</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>