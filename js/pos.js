// POS Cart Logic
let cart = [];
let manualIdCounter = -1;

function addToCart(id, name, price) {
    const existing = cart.find(item => item.id === id && item.id > 0);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.push({ id, name, price: parseFloat(price), qty: 1 });
    }
    renderCart();
}

function addManualItem() {
    const name = document.getElementById('manualName').value.trim();
    const price = parseFloat(document.getElementById('manualPrice').value);
    const qty = parseInt(document.getElementById('manualQty').value) || 1;

    if (!name || isNaN(price) || price <= 0) {
        alert('Please enter a valid item name and price.');
        return;
    }

    cart.push({ id: manualIdCounter--, name, price, qty });
    closeManualItem();
    renderCart();

    document.getElementById('manualName').value = '';
    document.getElementById('manualPrice').value = '';
    document.getElementById('manualQty').value = '1';
}

function openManualItem() {
    document.getElementById('manualModal').classList.add('show');
    setTimeout(() => document.getElementById('manualName').focus(), 100);
}

function closeManualItem() {
    document.getElementById('manualModal').classList.remove('show');
}

function updateQty(id, change) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.qty += change;
    if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart();
}

function clearCart() {
    cart = [];
    renderCart();
    document.getElementById('paymentAmount').value = '';
    updateChange();
}

function getCartTotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart">No items yet.<br>Tap a product to add.</div>';
        totalEl.textContent = '₱0.00';
        checkoutBtn.disabled = true;
        updateChange();
        return;
    }

    let html = '';
    cart.forEach(item => {
        const subtotal = item.price * item.qty;
        const isManual = item.id < 0;
        html += `
            <div class="cart-item">
                <div class="info">
                    <div class="name">${item.name}${isManual ? ' <small style="color:#64748b;">(manual)</small>' : ''}</div>
                    <div class="unit-price">₱${item.price.toFixed(2)} each</div>
                </div>
                <div class="qty-controls">
                    <button type="button" onclick="updateQty(${item.id}, -1)">−</button>
                    <span class="qty">${item.qty}</span>
                    <button type="button" onclick="updateQty(${item.id}, 1)">+</button>
                </div>
                <div class="subtotal">₱${subtotal.toFixed(2)}</div>
                <button type="button" class="remove" onclick="removeFromCart(${item.id})" title="Remove">×</button>
            </div>`;
    });

    container.innerHTML = html;
    totalEl.textContent = '₱' + getCartTotal().toFixed(2);
    checkoutBtn.disabled = false;
    updateChange();
}

function updateChange() {
    const paymentInput = document.getElementById('paymentAmount');
    const changeDisplay = document.getElementById('changeDisplay');
    const changeValue = document.getElementById('changeValue');

    const total = getCartTotal();
    const payment = parseFloat(paymentInput.value) || 0;
    const change = payment - total;

    if (paymentInput.value === '' || cart.length === 0) {
        changeDisplay.style.display = 'none';
        return;
    }

    changeDisplay.style.display = 'flex';
    changeValue.textContent = '₱' + Math.abs(change).toFixed(2);

    if (change < 0) {
        changeDisplay.classList.add('negative');
        changeDisplay.querySelector('.label').textContent = 'Insufficient';
    } else {
        changeDisplay.classList.remove('negative');
        changeDisplay.querySelector('.label').textContent = 'Change';
    }
}

function filterProducts() {
    const query = document.getElementById('productSearch').value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        card.style.display = card.dataset.name.toLowerCase().includes(query) ? '' : 'none';
    });
}

function openCheckout() {
    if (cart.length === 0) return;
    const total = getCartTotal();
    const payment = parseFloat(document.getElementById('paymentAmount').value) || 0;

    if (payment < total) {
        alert('Payment amount is less than the total.');
        return;
    }

    document.getElementById('modalTotal').textContent = '₱' + total.toFixed(2);
    document.getElementById('modalPayment').textContent = '₱' + payment.toFixed(2);
    document.getElementById('modalChange').textContent = '₱' + (payment - total).toFixed(2);

    let itemsHtml = '';
    cart.forEach(item => {
        itemsHtml += `<div style="display:flex;justify-content:space-between;font-size:0.9rem;margin-bottom:4px;">
            <span>${item.qty} × ${item.name}</span>
            <span>₱${(item.price * item.qty).toFixed(2)}</span>
        </div>`;
    });
    document.getElementById('modalItems').innerHTML = itemsHtml;
    document.getElementById('checkoutModal').classList.add('show');
}

function closeCheckout() {
    document.getElementById('checkoutModal').classList.remove('show');
}

function confirmSale() {
    const total = getCartTotal();
    const payment = parseFloat(document.getElementById('paymentAmount').value) || 0;

    document.getElementById('formCart').value = JSON.stringify(cart);
    document.getElementById('formTotal').value = total.toFixed(2);
    document.getElementById('formPayment').value = payment.toFixed(2);
    document.getElementById('formChange').value = (payment - total).toFixed(2);
    document.getElementById('saleForm').submit();
}

document.addEventListener('DOMContentLoaded', () => {
    renderCart();
    const paymentInput = document.getElementById('paymentAmount');
    if (paymentInput) paymentInput.addEventListener('input', updateChange);
});