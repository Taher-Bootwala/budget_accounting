</main> <!-- End Main Content -->
</div> <!-- End App Container -->

<!-- Payment Modal (Hidden by default) -->
<div id="paymentModal" class="modal-overlay hidden"
    style="position: fixed; inset: 0; display: none; align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal" style="width: 100%; max-width: 400px; padding: 24px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div
                style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.1); color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 24px;">
                <i class="ri-secure-payment-line"></i>
            </div>
            <h3 class="card-title">Secure Payment</h3>
            <p style="color: var(--text-muted); font-size: 13px;">Processing simulate via Razorpay</p>
        </div>

        <div style="margin-bottom: 24px; text-align: center;">
            <div style="font-size: 32px; font-weight: 700; color: var(--text-main);" id="payAmount">₹0.00</div>
        </div>

        <form id="paymentForm" onsubmit="processPayment(event)">
            <input type="hidden" id="payDocId" name="document_id">
            <input type="hidden" id="payAmountInput" name="amount">

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                Pay Now
            </button>
            <button type="button" class="btn btn-secondary" onclick="closePaymentModal()"
                style="width: 100%; margin-top: 12px;">
                Cancel
            </button>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="/Furniture/assets/js/app.js"></script>
<script>
    // Global Search Toggle
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');

    // Ctrl+K Shortcut
    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', debounce(async (e) => {
            const query = e.target.value;
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
                return;
            }

            try {
                const response = await fetch(`/Furniture/api/search.php?q=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.results && data.results.length > 0) {
                    searchResults.innerHTML = data.results.map(item => `
                        <a href="${item.url}" class="search-item">
                            <div class="search-icon">${item.icon}</div>
                            <div>
                                <div class="search-title">${item.title}</div>
                                <div class="search-subtitle">${item.subtitle}</div>
                            </div>
                        </a>
                    `).join('');
                    searchResults.classList.remove('hidden');
                } else {
                    searchResults.innerHTML = '<div style="padding: 12px; color: var(--text-muted); text-align: center;">No results found</div>';
                    searchResults.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300));

        // Hide search on outside click
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
</script>
</body>

</html>