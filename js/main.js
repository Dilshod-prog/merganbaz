// Common JavaScript functions for Merganbaz Admin Panel

// Format numbers
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

// Show/hide loading spinner
function showLoading(element) {
    if (typeof element === 'string') {
        element = document.getElementById(element);
    }
    if (element) {
        element.innerHTML = '<div class="spinner"></div>';
    }
}

// Auto-refresh for specific pages (optional)
let autoRefreshInterval = null;

function startAutoRefresh(seconds = 30) {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    autoRefreshInterval = setInterval(() => {
        // Only refresh if user is active (not typing, not in modal)
        const activeElement = document.activeElement;
        const modals = document.querySelectorAll('.modal.show');
        
        if (modals.length === 0 && activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
            location.reload();
        }
    }, seconds * 1000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Confirm actions
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Toast notification (simple alert replacement)
function showToast(message, type = 'info') {
    // For now, use alert. Can be enhanced with custom toast UI
    alert(message);
}

// Debounce function for search inputs
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

// Phone number formatter
function formatPhoneInput(input) {
    let value = input.value.replace(/\D/g, '');
    
    if (value.length > 0 && !value.startsWith('998')) {
        if (value.startsWith('0')) {
            value = '998' + value.substring(1);
        } else if (value.length <= 9) {
            value = '998' + value;
        }
    }
    
    if (value.length > 12) {
        value = value.substring(0, 12);
    }
    
    if (value.length >= 3) {
        input.value = '+' + value.substring(0, 3) + value.substring(3);
    } else {
        input.value = value ? '+' + value : '';
    }
}

// Auto-format phone inputs on page
document.addEventListener('DOMContentLoaded', function() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneInput(this);
        });
    });
});

// Escape key to close modals
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            modal.classList.remove('show');
        });
    }
});

// Prevent form submission on Enter key in modals (except textarea)
document.addEventListener('keydown', function(event) {
    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
        const form = event.target.closest('form');
        if (form && form.closest('.modal')) {
            event.preventDefault();
        }
    }
});

// Table search functionality
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const filter = input.value.toUpperCase();
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell) {
                const textValue = cell.textContent || cell.innerText;
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        row.style.display = found ? '' : 'none';
    }
}

console.log('Merganbaz Admin Panel - Ready');
