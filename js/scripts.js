// Form validation for order form
function validateOrderForm() {
    const quantities = document.querySelectorAll('.quantity-input');
    let hasItems = false;
    quantities.forEach(input => {
        if (parseInt(input.value) > 0) hasItems = true;
    });
    if (!hasItems) {
        alert('Please select at least one clothing item.');
        return false;
    }
    const dropOffDate = document.getElementById('drop_off_date');
    if (!dropOffDate || !dropOffDate.value) {
        alert('Please select a drop-off date.');
        return false;
    }
    const paymentMethod = document.getElementById('payment_method');
    if (!paymentMethod || !paymentMethod.value) {
        alert('Please select a payment method.');
        return false;
    }
    return true;
}

// Form validation for register form
function validateRegisterForm() {
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const role = document.getElementById('role');
    if (!name || !name.value.trim()) {
        alert('Please enter your full name.');
        return false;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailRegex.test(email.value)) {
        alert('Please enter a valid email address.');
        return false;
    }
    if (!password || password.value.length < 6) {
        alert('Password must be at least 6 characters long.');
        return false;
    }
    if (!role || !role.value) {
        alert('Please select a role.');
        return false;
    }
    return true;
}

// Form validation for inventory form
function validateInventoryForm() {
    const itemName = document.getElementById('item_name');
    const quantity = document.getElementById('quantity');
    const unit = document.getElementById('unit');
    const reorderLevel = document.getElementById('reorder_level');
    if (!itemName || !itemName.value.trim()) {
        alert('Please enter the item name.');
        return false;
    }
    if (!quantity || quantity.value < 0) {
        alert('Please enter a valid quantity.');
        return false;
    }
    if (!unit || !unit.value.trim()) {
        alert('Please enter the unit.');
        return false;
    }
    if (!reorderLevel || reorderLevel.value < 0) {
        alert('Please enter a valid reorder level.');
        return false;
    }
    return true;
}

// Modal handling
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
};

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tr');
    let csv = [];
    for (let row of rows) {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => `"${col.textContent.replace(/"/g, '""')}"`).join(',');
        csv.push(rowData);
    }

    const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Dashboard interactivity
document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle
    const toggleButton = document.getElementById('toggle-sidebar');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    if (toggleButton && sidebar && mainContent) {
        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
        });
    }

    // Theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = body.getAttribute('data-theme') === 'dark';
            body.setAttribute('data-theme', isDark ? 'light' : 'dark');
            themeToggle.innerHTML = `<i class="fas fa-${isDark ? 'moon' : 'sun'}"></i>`;
        });
    }

    // Card toggle
    const toggleIcons = document.querySelectorAll('.toggle-card');
    toggleIcons.forEach(icon => {
        icon.addEventListener('click', () => {
            const targetId = icon.getAttribute('data-target');
            const content = document.getElementById(targetId);
            if (content) {
                content.classList.toggle('collapsed');
                icon.classList.toggle('active');
            }
        });
    });

    // Input animation for forms
    const inputs = document.querySelectorAll('.input-group input, .input-group textarea, .input-group select');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            if (!input.value) {
                input.parentElement.classList.remove('focused');
            }
        });
    });

    // Sortable tables
    const tables = document.querySelectorAll('.sortable-table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.addEventListener('click', () => {
                const key = header.getAttribute('data-sort');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = !header.classList.contains('asc');

                rows.sort((a, b) => {
                    const aCell = a.querySelector(`td:nth-child(${Array.from(header.parentElement.children).indexOf(header) + 1})`);
                    const bCell = b.querySelector(`td:nth-child(${Array.from(header.parentElement.children).indexOf(header) + 1})`);
                    let aVal = aCell ? aCell.textContent : '';
                    let bVal = bCell ? bCell.textContent : '';

                    if (key.includes('amount') || key.includes('quantity') || key.includes('id') || key.includes('reorder_level')) {
                        aVal = parseFloat(aVal) || 0;
                        bVal = parseFloat(bVal) || 0;
                    } else if (key.includes('date')) {
                        aVal = new Date(aVal).getTime() || 0;
                        bVal = new Date(bVal).getTime() || 0;
                    } else {
                        aVal = aVal.toLowerCase();
                        bVal = bVal.toLowerCase();
                    }

                    return isAsc ? (aVal > bVal ? 1 : -1) : (aVal < bVal ? 1 : -1);
                });

                tbody.innerHTML = '';
                rows.forEach(row => tbody.appendChild(row));

                headers.forEach(h => h.classList.remove('asc', 'desc'));
                header.classList.add(isAsc ? 'asc' : 'desc');
            });
        });
    });

    // Status filter for recent orders
    const statusFilter = document.getElementById('status-filter');
    const recentOrdersTable = document.getElementById('recent-orders-table');
    if (statusFilter && recentOrdersTable) {
        statusFilter.addEventListener('change', () => {
            const status = statusFilter.value;
            const rows = recentOrdersTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (!status || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

// Fetch orders dynamically for order_tracking.php
function fetchOrders() {
    fetch('order_tracking.php?ajax=1')
        .then(response => response.json())
        .then(data => {
            const orderList = document.getElementById('order-list');
            if (orderList) {
                const tbody = orderList.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = '';
                    data.forEach(order => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${order.order_id}</td>
                            <td>${order.tag_number}</td>
                            <td>${order.items}</td>
                            <td>${order.status}</td>
                            <td>${new Date(order.drop_off_date).toLocaleString()}</td>
                            <td>${(order.total_amount || 0).toFixed(2)}</td>
                            <td>${order.payment_status || 'N/A'}</td>
                            <td>
                                <a href="receipt.php?order_id=${order.order_id}" class="action-button">View Receipt</a>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            }
        })
        .catch(error => console.error('Error fetching orders:', error));
}

// Initialize order tracking
if (document.getElementById('order-list')) {
    fetchOrders();
    setInterval(fetchOrders, 30000);
}