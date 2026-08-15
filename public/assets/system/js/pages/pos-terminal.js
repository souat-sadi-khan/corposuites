var posCart = {};

function posFindProductCard(productId) {
    return $('.pos-product-card[data-id="' + productId + '"]');
}

function posAddToCart(productId) {
    var card = posFindProductCard(productId);
    if (!card.length) return;

    if (posCart[productId]) {
        posCart[productId].quantity += 1;
    } else {
        posCart[productId] = {
            product_id: productId,
            name: card.data('name'),
            sku: card.data('sku'),
            unit_price: parseFloat(card.data('price')) || 0,
            quantity: 1,
            discount: 0
        };
    }

    posRenderCart();
}

function posRemoveFromCart(productId) {
    delete posCart[productId];
    posRenderCart();
}

function posRenderCart() {
    var body = $('#posCartBody');
    var productIds = Object.keys(posCart);

    if (!productIds.length) {
        body.html('<tr class="pos-cart-empty-row"><td colspan="4" class="text-center text-muted py-3">Cart is empty — click a product to add it</td></tr>');
        posRecalculateTotals();
        return;
    }

    var rows = '';
    productIds.forEach(function (productId) {
        var item = posCart[productId];
        rows += `
            <tr data-product-id="${productId}">
                <td>${item.name}<br><small class="text-muted">${item.sku}</small></td>
                <td><input type="number" min="0.01" step="0.01" class="form-control form-control-sm pos-cart-qty" value="${item.quantity}" data-product-id="${productId}"></td>
                <td>${item.unit_price.toFixed(2)}</td>
                <td><button type="button" class="btn-nx-outline btn-sm pos-cart-remove" data-product-id="${productId}"><i class="ri-close-line"></i></button></td>
            </tr>
        `;
    });

    body.html(rows);
    posRecalculateTotals();
}

$(document).on('click', '.pos-product-card', function () {
    posAddToCart($(this).data('id').toString());
});

$(document).on('click', '.pos-cart-remove', function () {
    posRemoveFromCart($(this).data('product-id').toString());
});

$(document).on('input', '.pos-cart-qty', function () {
    var productId = $(this).data('product-id').toString();
    var qty = parseFloat($(this).val()) || 0;
    if (posCart[productId]) {
        posCart[productId].quantity = qty;
    }
    posRecalculateTotals();
});

$(document).on('input', '#posAmountTendered', function () {
    posRecalculateTotals();
});

function posRecalculateTotals() {
    var subtotal = 0;
    var discountTotal = 0;

    Object.keys(posCart).forEach(function (productId) {
        var item = posCart[productId];
        subtotal += (item.quantity * item.unit_price);
        discountTotal += item.discount;
    });

    var grandTotal = subtotal - discountTotal;
    var tendered = parseFloat($('#posAmountTendered').val()) || 0;
    var changeDue = Math.max(0, tendered - grandTotal);

    $('#posSubtotal').text(subtotal.toFixed(2));
    $('#posDiscount').text(discountTotal.toFixed(2));
    $('#posGrandTotal').text(grandTotal.toFixed(2));
    $('#posChangeDue').text(changeDue.toFixed(2));
}

// =====================================================
// Product search filter
// =====================================================
$('#posProductSearch').on('keyup', function () {
    var term = $(this).val().toLowerCase();
    $('.pos-product-card').each(function () {
        var card = $(this);
        var matches = card.data('name').toLowerCase().indexOf(term) !== -1
            || (card.data('sku') || '').toLowerCase().indexOf(term) !== -1;
        card.toggle(matches);
    });
});

// =====================================================
// Checkout
// =====================================================
$('#posCheckoutBtn').on('click', function () {
    var productIds = Object.keys(posCart);
    if (!productIds.length) {
        alert('Add at least one product to the cart before checking out.');
        return;
    }

    var items = productIds.map(function (productId) {
        var item = posCart[productId];
        return {
            product_id: item.product_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            discount: item.discount
        };
    });

    $('#posCheckoutBtn').hide();
    $('#posCheckoutBtnLoading').show();

    $.ajax({
        url: window.posCheckoutUrl,
        type: 'POST',
        data: {
            customer_id: $('#posCustomerSelect').val(),
            payment_method: $('#posPaymentMethod').val(),
            amount_tendered: $('#posAmountTendered').val(),
            items: items
        },
        success: function (res) {
            if (res.status) {
                posCart = {};
                posRenderCart();
                $('#posAmountTendered').val('');
                $('#posCustomerSelect').val('').trigger('change');
                window.open(res.receipt_url, '_blank');
            } else {
                alert(res.message || 'Unable to complete the sale.');
            }
        },
        error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var messages = Object.values(xhr.responseJSON.errors).flat().join('\n');
                alert(messages);
            } else {
                alert('Something went wrong while completing the sale.');
            }
        },
        complete: function () {
            $('#posCheckoutBtnLoading').hide();
            $('#posCheckoutBtn').show();
        }
    });
});
