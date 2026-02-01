/**
 * Budget Accounting & Analytics ERP Module
 * Main JavaScript - app.js
 */

// Global Search Functionality
document.addEventListener('DOMContentLoaded', function () {
    initGlobalSearch();
    initModals();
    initCostCenterPreview();
    initExportButtons();
    initSidebarToggle();
});

/**
 * Global Search (Ctrl+K shortcut)
 */
function initGlobalSearch() {
    const searchInput = document.getElementById('globalSearchInput');
    const searchResults = document.getElementById('searchResults');

    if (!searchInput) return;

    // Keyboard shortcut Ctrl+K
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }

        // Escape to close
        if (e.key === 'Escape' && searchResults) {
            searchResults.classList.remove('active');
            searchInput.blur();
        }
    });

    // Search input handler with debounce
    let searchTimeout;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            if (searchResults) searchResults.classList.remove('active');
            return;
        }

        searchTimeout = setTimeout(() => performSearch(query), 300);
    });

    // Close results when clicking outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && searchResults && !searchResults.contains(e.target)) {
            searchResults.classList.remove('active');
        }
    });
}

/**
 * Perform AJAX search
 */
async function performSearch(query) {
    const searchResults = document.getElementById('searchResults');
    if (!searchResults) return;

    try {
        const response = await fetch(`/Furniture/api/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data.results && data.results.length > 0) {
            searchResults.innerHTML = data.results.map(item => `
                <a href="${item.url}" class="search-result-item">
                    <div class="result-icon">${item.icon}</div>
                    <div class="result-info">
                        <h4>${item.title}</h4>
                        <span>${item.subtitle}</span>
                    </div>
                </a>
            `).join('');
            searchResults.classList.add('active');
        } else {
            searchResults.innerHTML = `
                <div class="search-result-item">
                    <div class="result-info">
                        <h4>No results found</h4>
                        <span>Try a different search term</span>
                    </div>
                </div>
            `;
            searchResults.classList.add('active');
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

/**
 * Modal functionality
 */
function initModals() {
    // Open modal
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            openModal(modalId);
        });
    });

    // Close modal
    document.querySelectorAll('.modal-close, [data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            closeModal(this.closest('.modal-overlay'));
        });
    });

    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalElement) {
    if (modalElement) {
        modalElement.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Auto Cost Center Preview
 */
function initCostCenterPreview() {
    const productSelect = document.getElementById('productSelect');
    const previewContainer = document.getElementById('costCenterPreview');

    if (!productSelect || !previewContainer) return;

    productSelect.addEventListener('change', async function () {
        const productId = this.value;
        if (!productId) {
            previewContainer.style.display = 'none';
            return;
        }

        try {
            const response = await fetch(`/Furniture/api/cost_center_preview.php?product_id=${productId}`);
            const data = await response.json();

            if (data.cost_center) {
                previewContainer.innerHTML = `
                    <span class="preview-icon">💡</span>
                    <span class="preview-text">
                        This transaction will be assigned to: 
                        <span class="preview-name">${data.cost_center}</span>
                    </span>
                `;
                previewContainer.style.display = 'flex';
            } else {
                previewContainer.innerHTML = `
                    <span class="preview-icon">ℹ️</span>
                    <span class="preview-text">
                        No automatic cost center assignment. Please select manually.
                    </span>
                `;
                previewContainer.style.display = 'flex';
            }
        } catch (error) {
            console.error('Preview error:', error);
        }
    });
}

/**
 * Export Buttons
 */
function initExportButtons() {
    document.querySelectorAll('[data-export]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const exportType = this.dataset.export;
            const filters = getCurrentFilters();

            window.location.href = `/Furniture/api/export.php?type=${exportType}&${new URLSearchParams(filters).toString()}`;
        });
    });
}

/**
 * Get current page filters
 */
function getCurrentFilters() {
    const filters = {};
    const urlParams = new URLSearchParams(window.location.search);

    for (const [key, value] of urlParams) {
        filters[key] = value;
    }

    return filters;
}

/**
 * Sidebar Toggle for Mobile
 */
function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');

    if (!toggleBtn || !sidebar) return;

    toggleBtn.addEventListener('click', function () {
        sidebar.classList.toggle('active');
    });
}

/**
 * Confirm Delete
 */
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

/**
 * Flash message auto-dismiss
 */
document.addEventListener('DOMContentLoaded', function () {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-10px)';
            setTimeout(() => msg.remove(), 300);
        }, 5000);
    });
});

/**
 * Document Line Items Management
 */
let lineItemIndex = 0;

function addLineItem() {
    const container = document.getElementById('lineItemsContainer');
    if (!container) return;

    const template = document.getElementById('lineItemTemplate');
    if (!template) return;

    const html = template.innerHTML.replace(/__INDEX__/g, lineItemIndex);
    const wrapper = document.createElement('div');
    wrapper.className = 'line-item';
    wrapper.innerHTML = html;
    container.appendChild(wrapper);

    lineItemIndex++;
    updateTotals();
}

function removeLineItem(btn) {
    const lineItem = btn.closest('.line-item');
    if (lineItem) {
        lineItem.remove();
        updateTotals();
    }
}

function updateLineTotal(row) {
    const qty = parseFloat(row.querySelector('.line-qty').value) || 0;
    const price = parseFloat(row.querySelector('.line-price').value) || 0;
    const total = qty * price;

    row.querySelector('.line-total').value = total.toFixed(2);
    updateTotals();
}

function updateTotals() {
    const totals = document.querySelectorAll('.line-total');
    let grandTotal = 0;

    totals.forEach(input => {
        grandTotal += parseFloat(input.value) || 0;
    });

    const grandTotalEl = document.getElementById('grandTotal');
    if (grandTotalEl) {
        grandTotalEl.textContent = formatCurrency(grandTotal);
    }

    const grandTotalInput = document.getElementById('grandTotalInput');
    if (grandTotalInput) {
        grandTotalInput.value = grandTotal.toFixed(2);
    }
}

/**
 * Format currency for display
 */
function formatCurrency(amount) {
    return '₹' + new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

/**
 * Simulated Razorpay Payment
 */
function initiatePayment(documentId, amount) {
    // Simulate Razorpay checkout
    const paymentModal = document.getElementById('paymentModal');
    if (!paymentModal) return;

    // Set values for the modal
    const input = document.getElementById('payAmountInput');
    if (input) {
        input.value = parseFloat(amount).toFixed(2);
        input.max = parseFloat(amount).toFixed(2); // Optional: warn if paying more
    }

    document.getElementById('payDocId').value = documentId;
    openModal('paymentModal');
}

function processPayment(event) {
    event.preventDefault(); // Prevent form submission

    const form = document.getElementById('paymentForm');
    if (!form) return;

    const docId = document.getElementById('payDocId').value;
    const amount = document.getElementById('payAmountInput').value;

    if (!amount || parseFloat(amount) <= 0) {
        showNotification('error', 'Please enter a valid amount');
        return;
    }

    // Simulate processing
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Processing...';

    setTimeout(() => {
        // Generate fake Razorpay ID
        const razorpayId = 'pay_' + Math.random().toString(36).substring(2, 15);

        // Submit to server
        fetch('/Furniture/api/process_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                document_id: docId,
                amount: amount,
                method: 'card',
                razorpay_payment_id: razorpayId
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = document.getElementById('paymentModal');
                    closeModal(modal);
                    showNotification('success', 'Payment successful!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('error', data.message || 'Payment failed');
                    btn.disabled = false;
                    btn.innerHTML = 'Pay Now';
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                showNotification('error', 'Payment failed. Please try again.');
                btn.disabled = false;
                btn.innerHTML = 'Pay Now';
            });
    }, 2000);
}

/**
 * Show notification toast
 */
function showNotification(type, message) {
    const container = document.getElementById('notificationContainer') || createNotificationContainer();

    const notification = document.createElement('div');
    notification.className = `flash-message ${type}`;
    notification.innerHTML = `
        <span>${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span>
        ${message}
    `;

    container.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notificationContainer';
    container.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; width: 320px;';
    document.body.appendChild(container);
    return container;
}

/**
 * Initialize Charts
 */
function initBudgetChart(canvasId, labels, budgetData, actualData) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Budget',
                    data: budgetData,
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Actual',
                    data: actualData,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function initPieChart(canvasId, labels, data, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            },
            cutout: '60%'
        }
    });
}
