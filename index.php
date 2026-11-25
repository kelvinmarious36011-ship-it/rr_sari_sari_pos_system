<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$user = currentUser();

// Handle sale submission
$message = '';
$messageType = '';
$lastTxnId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_sale') {
    $cartJson = $_POST['cart'] ?? '[]';
    $total = floatval($_POST['total'] ?? 0);
    $payment = floatval($_POST['payment'] ?? 0);
    $change = floatval($_POST['change'] ?? 0);
    $cart = json_decode($cartJson, true);

    if (!empty($cart) && $total > 0 && $payment >= $total) {
        $code = 'TXN' . date('YmdHis') . rand(10, 99);

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO transactions (transaction_code, total_amount, payment_amount, change_amount) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sddd", $code, $total, $payment, $change);
            $stmt->execute();
            $txnId = $conn->insert_id;
            $stmt->close();

            foreach ($cart as $item) {
                $pid = (!empty($item['id']) && $item['id'] > 0) ? intval($item['id']) : null;
                $pname = $item['name'];
                $qty = intval($item['qty']);
                $price = floatval($item['price']);
                $sub = $price * $qty;

                if ($pid) {
                    $itemStmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                    $itemStmt->bind_param("iisidd", $txnId, $pid, $pname, $qty, $price, $sub);
                } else {
                    $itemStmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, NULL, ?, ?, ?, ?)");
                    $itemStmt->bind_param("isidd", $txnId, $pname, $qty, $price, $sub);
                }
                $itemStmt->execute();
                $itemStmt->close();

                // Decrease stock only for real products
                if ($pid) {
                    $conn->query("UPDATE products SET stock = GREATEST(0, stock - $qty) WHERE id = $pid");
                }
            }

            $conn->commit();
            $lastTxnId = $txnId;
            $message = "Sale completed! Transaction: $code";
            $messageType = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error saving sale: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = "Invalid sale data. Please try again.";
        $messageType = 'error';
    }
}

// Fetch products
$products = $conn->query("SELECT * FROM products ORDER BY name ASC");

// Low stock count for badge
$lowStockCount = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock <= 5")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>rr_sari_sari_pos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand">rr_sari_sari_pos <span>• POS</span></div>
        <div class="nav-links">
            <a href="index.php" class="active">POS</a>
            <a href="products.php">Products <?= $lowStockCount > 0 ? "<span style='background:#dc2626;color:white;font-size:0.7rem;padding:1px 6px;border-radius:10px;margin-left:2px;'>$lowStockCount</span>" : '' ?></a>
            <a href="transactions.php">Sales Log</a>
            <a href="daily.php">Daily Report</a>
            <a href="logout.php" style="opacity:0.85;">Logout (<?= htmlspecialchars($user['full_name']) ?>)</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
                <?php if ($lastTxnId): ?>
                    &nbsp; <a href="receipt.php?id=<?= $lastTxnId ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-left:8px;">Print Receipt</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="pos-grid">
            <!-- PRODUCTS -->
            <div class="card">
                <div class="card-header">
                    <h2>Products</h2>
                    <button type="button" class="btn btn-outline btn-sm" onclick="openManualItem()">+ Manual Item</button>
                </div>
                <div class="product-search">
                    <input type="text" id="productSearch" placeholder="Search products..." oninput="filterProducts()">
                </div>
                <div class="products-grid" id="productsGrid">
                    <?php while ($p = $products->fetch_assoc()): ?>
                        <?php $isLow = $p['stock'] <= 5; ?>
                        <div class="product-card <?= $isLow ? 'low-stock' : '' ?>"
                             data-name="<?= htmlspecialchars($p['name']) ?>"
                             onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['price'] ?>)">
                            <div class="name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="price">₱<?= number_format($p['price'], 2) ?></div>
                            <div class="stock" style="<?= $isLow ? 'color:#dc2626;font-weight:600;' : '' ?>">
                                Stock: <?= $p['stock'] ?><?= $isLow ? ' ⚠' : '' ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- CART -->
            <div class="card">
                <div class="card-header">
                    <h2>Current Sale</h2>
                    <button type="button" class="btn btn-outline btn-sm" onclick="clearCart()">Clear</button>
                </div>

                <div class="cart-items" id="cartItems">
                    <div class="empty-cart">No items yet.<br>Tap a product to add.</div>
                </div>

                <div class="totals">
                    <div class="totals-row grand">
                        <span>Total</span>
                        <span id="cartTotal">₱0.00</span>
                    </div>
                </div>

                <div class="payment-section">
                    <label>Cash Received (₱)</label>
                    <input type="number" id="paymentAmount" step="0.01" min="0" placeholder="0.00" inputmode="decimal">
                    
                    <div class="change-display" id="changeDisplay" style="display:none;">
                        <span class="label">Change</span>
                        <span id="changeValue">₱0.00</span>
                    </div>
                </div>

                <button type="button" class="btn btn-success" id="checkoutBtn" disabled onclick="openCheckout()">
                    Complete Sale
                </button>
            </div>
        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    <div class="modal-overlay" id="checkoutModal">
        <div class="modal">
            <h3>Confirm Sale</h3>
            <div id="modalItems" style="margin-bottom:1rem;max-height:180px;overflow-y:auto;"></div>
            <div style="border-top:1px solid #e2e8f0;padding-top:0.75rem;">
                <div class="totals-row"><span>Total</span><strong id="modalTotal">₱0.00</strong></div>
                <div class="totals-row"><span>Cash</span><span id="modalPayment">₱0.00</span></div>
                <div class="totals-row" style="font-size:1.1rem;font-weight:700;color:#0d9488;">
                    <span>Change</span><span id="modalChange">₱0.00</span>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeCheckout()">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmSale()">Confirm & Save</button>
            </div>
        </div>
    </div>

    <!-- MANUAL ITEM MODAL -->
    <div class="modal-overlay" id="manualModal">
        <div class="modal">
            <h3>Add Manual Item</h3>
            <p style="font-size:0.9rem;color:#64748b;margin-bottom:1rem;">Use this for items not in the product list.</p>
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" id="manualName" placeholder="e.g. Plastic Bag, Ice, etc.">
            </div>
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" id="manualPrice" step="0.01" min="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" id="manualQty" min="1" value="1">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeManualItem()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addManualItem()">Add to Cart</button>
            </div>
        </div>
    </div>

    <!-- Hidden form for submission -->
    <form id="saleForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="save_sale">
        <input type="hidden" name="cart" id="formCart">
        <input type="hidden" name="total" id="formTotal">
        <input type="hidden" name="payment" id="formPayment">
        <input type="hidden" name="change" id="formChange">
    </form>

    <script src="js/pos.js"></script>
</body>
</html>