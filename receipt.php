<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die('Invalid receipt.');
}

$txn = $conn->query("SELECT * FROM transactions WHERE id = $id")->fetch_assoc();
if (!$txn) {
    die('Transaction not found.');
}

$items = $conn->query("SELECT * FROM transaction_items WHERE transaction_id = $id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt • <?= htmlspecialchars($txn['transaction_code']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f1f5f9;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .actions { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn {
            padding: 10px 20px; border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            font-family: system-ui, sans-serif;
        }
        .btn-print { background: #1a73e8; color: white; }
        .btn-close { background: #e2e8f0; color: #1e293b; }
        .receipt {
            background: white; width: 300px; padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 4px;
        }
        .center { text-align: center; }
        .store-name { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .store-addr { font-size: 11px; color: #555; margin-top: 2px; }
        .line { border-top: 1px dashed #999; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 3px; }
        .item-name { max-width: 160px; }
        .total-row { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .thanks { font-size: 11px; margin-top: 8px; }
        @media print {
            body { background: white; padding: 0; }
            .actions { display: none; }
            .receipt { box-shadow: none; width: 100%; max-width: 280px; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn btn-print" onclick="window.print()">🖨 Print Receipt</button>
        <button class="btn btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="receipt">
        <div class="center">
            <div class="store-name">RR SARI-SARI STORE</div>
            <div class="store-addr">Ilaud, Inabanga, Bohol</div>
        </div>
        <div class="line"></div>
        <div class="row">
            <span><?= htmlspecialchars($txn['transaction_code']) ?></span>
        </div>
        <div class="row">
            <span><?= date('M d, Y h:i A', strtotime($txn['transaction_date'])) ?></span>
        </div>
        <div class="line"></div>

        <?php foreach ($items as $item): ?>
            <div class="row">
                <span class="item-name"><?= $item['quantity'] ?> x <?= htmlspecialchars($item['product_name']) ?></span>
                <span>₱<?= number_format($item['subtotal'], 2) ?></span>
            </div>
            <div class="row" style="font-size:10px;color:#666;margin-bottom:6px;">
                <span>@ ₱<?= number_format($item['unit_price'], 2) ?></span>
            </div>
        <?php endforeach; ?>

        <div class="line"></div>
        <div class="row total-row">
            <span>TOTAL</span>
            <span>₱<?= number_format($txn['total_amount'], 2) ?></span>
        </div>
        <div class="row">
            <span>Cash</span>
            <span>₱<?= number_format($txn['payment_amount'], 2) ?></span>
        </div>
        <div class="row">
            <span>Change</span>
            <span>₱<?= number_format($txn['change_amount'], 2) ?></span>
        </div>
        <div class="line"></div>
        <div class="center thanks">Thank you for your purchase!</div>
        <div class="center thanks">Please come again.</div>
    </div>
</body>
</html>