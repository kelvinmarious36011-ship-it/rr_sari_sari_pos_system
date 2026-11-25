<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$user = currentUser();
$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_sales,
        COALESCE(SUM(total_amount), 0) AS total_revenue,
        COALESCE(SUM(payment_amount), 0) AS total_cash_in,
        COALESCE(SUM(change_amount), 0) AS total_change
    FROM transactions
    WHERE DATE(transaction_date) = ?
");
$stmt->bind_param("s", $date);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

$topStmt = $conn->prepare("
    SELECT ti.product_name, SUM(ti.quantity) AS qty_sold, SUM(ti.subtotal) AS revenue
    FROM transaction_items ti
    JOIN transactions t ON t.id = ti.transaction_id
    WHERE DATE(t.transaction_date) = ?
    GROUP BY ti.product_name
    ORDER BY qty_sold DESC
    LIMIT 10
");
$topStmt->bind_param("s", $date);
$topStmt->execute();
$topItems = $topStmt->get_result();

$txnStmt = $conn->prepare("
    SELECT t.*, 
           GROUP_CONCAT(CONCAT(ti.quantity, 'x ', ti.product_name) SEPARATOR ', ') AS items
    FROM transactions t
    LEFT JOIN transaction_items ti ON t.id = ti.transaction_id
    WHERE DATE(t.transaction_date) = ?
    GROUP BY t.id
    ORDER BY t.transaction_date ASC
");
$txnStmt->bind_param("s", $date);
$txnStmt->execute();
$dayTxns = $txnStmt->get_result();

$lowStockCount = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock <= 5")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report • rr_sari_sari_pos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand">rr_sari_sari_pos <span>• POS</span></div>
        <div class="nav-links">
            <a href="index.php">POS</a>
            <a href="products.php">Products <?= $lowStockCount > 0 ? "<span style='background:#dc2626;color:white;font-size:0.7rem;padding:1px 6px;border-radius:10px;margin-left:2px;'>$lowStockCount</span>" : '' ?></a>
            <a href="transactions.php">Sales Log</a>
            <a href="daily.php" class="active">Daily Report</a>
            <a href="logout.php" style="opacity:0.85;">Logout (<?= htmlspecialchars($user['full_name']) ?>)</a>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">Daily Reconciliation</h1>

        <form method="GET" class="filters" style="margin-bottom:1.5rem;">
            <div class="form-group">
                <label>Select Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Show Report</button>
            <a href="daily.php" class="btn btn-outline">Today</a>
        </form>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Sales</div>
                <div class="value"><?= $summary['total_sales'] ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Revenue</div>
                <div class="value">₱<?= number_format($summary['total_revenue'], 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Cash Received</div>
                <div class="value">₱<?= number_format($summary['total_cash_in'], 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Change Given</div>
                <div class="value">₱<?= number_format($summary['total_change'], 2) ?></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;" class="daily-grid">
            <div class="card">
                <div class="card-header">
                    <h2>Top Selling Items</h2>
                </div>
                <?php if ($topItems->num_rows === 0): ?>
                    <p class="text-muted text-center" style="padding:1.5rem;">No sales on this date.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $topItems->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= $item['qty_sold'] ?></td>
                                        <td>₱<?= number_format($item['revenue'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Cash Reconciliation</h2>
                </div>
                <p style="margin-bottom:1rem;color:var(--muted);font-size:0.95rem;">
                    At end of day, count the actual cash in the drawer and compare with the system total.
                </p>
                <div class="totals" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                    <div class="totals-row">
                        <span>System Total Revenue</span>
                        <strong>₱<?= number_format($summary['total_revenue'], 2) ?></strong>
                    </div>
                    <div class="totals-row" style="margin-top:0.75rem;">
                        <span>Expected Cash in Drawer</span>
                        <strong style="color:#0d9488;">₱<?= number_format($summary['total_revenue'], 2) ?></strong>
                    </div>
                </div>
                <p style="margin-top:1rem;font-size:0.9rem;color:var(--muted);">
                    <strong>Tip:</strong> If actual cash ≠ system total, review the transaction log for missing or incorrect entries.
                </p>
            </div>
        </div>

        <div class="card" style="margin-top:1.25rem;">
            <div class="card-header">
                <h2>All Transactions — <?= date('F d, Y', strtotime($date)) ?></h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Code</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Cash</th>
                            <th>Change</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($dayTxns->num_rows === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">No transactions on this date.</td></tr>
                        <?php else: ?>
                            <?php while ($t = $dayTxns->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('h:i A', strtotime($t['transaction_date'])) ?></td>
                                    <td><?= htmlspecialchars($t['transaction_code']) ?></td>
                                    <td style="max-width:300px;font-size:0.9rem;"><?= htmlspecialchars($t['items'] ?? '—') ?></td>
                                    <td><strong>₱<?= number_format($t['total_amount'], 2) ?></strong></td>
                                    <td>₱<?= number_format($t['payment_amount'], 2) ?></td>
                                    <td>₱<?= number_format($t['change_amount'], 2) ?></td>
                                    <td>
                                        <a href="receipt.php?id=<?= $t['id'] ?>" target="_blank" class="btn btn-outline btn-sm">Print</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        @media (max-width: 800px) {
            .daily-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</body>
</html>