/**
 * Moses Group of Companies Overseas Recruitment HRMS Main JavaScript & Component Initializer
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Collapse / Expand Toggle
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const sidebar = document.getElementById('sidebar');
    const navbar = document.getElementById('navbar');
    const mainContent = document.getElementById('main-content');
    const footer = document.querySelector('footer');

    // Restore saved sidebar collapsed state
    const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    if (isCollapsed) {
        applySidebarState(true);
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function () {
            const currentlyCollapsed = sidebar.classList.contains('sidebar-collapsed');
            const newState = !currentlyCollapsed;
            applySidebarState(newState);
            localStorage.setItem('sidebar_collapsed', newState);
        });
    }

    function applySidebarState(collapsed) {
        if (!sidebar) return;
        const action = collapsed ? 'add' : 'remove';
        sidebar.classList[action]('sidebar-collapsed');
        if (navbar) navbar.classList[action]('sidebar-collapsed');
        if (mainContent) mainContent.classList[action]('sidebar-collapsed');
        if (footer) footer.classList[action]('sidebar-collapsed');
    }

    // 2. Initialize DataTables with Highly Visible Solid Export Buttons
    if (window.jQuery && $.fn.DataTable) {
        $.fn.dataTable.ext.errMode = 'none'; // Suppress DataTables warning alerts
        $('.datatable-init').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    retrieve: true,
                    responsive: true,
                    dom: '<"row mb-3"<"col-md-6"B><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [
                        { extend: 'csv', className: 'btn btn-sm btn-info text-white fw-bold me-1 shadow-sm', text: '<i class="fa-solid fa-file-csv me-1"></i> CSV' },
                        { extend: 'excel', className: 'btn btn-sm btn-success text-white fw-bold me-1 shadow-sm', text: '<i class="fa-solid fa-file-excel me-1"></i> Excel' },
                        { extend: 'pdf', className: 'btn btn-sm btn-danger text-white fw-bold me-1 shadow-sm', text: '<i class="fa-solid fa-file-pdf me-1"></i> PDF' },
                        { extend: 'print', className: 'btn btn-sm btn-primary text-white fw-bold shadow-sm', text: '<i class="fa-solid fa-print me-1"></i> Print' }
                    ],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search records..."
                    }
                });
            }
        });
    }
});

// Helper for SweetAlert Confirm Dialog
function confirmAction(title, text, confirmBtnText, callback) {
    Swal.fire({
        title: title || 'Are you sure?',
        text: text || "You won't be able to revert this action!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2B7A9E',
        cancelButtonColor: '#E74C3C',
        confirmButtonText: confirmBtnText || 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

// Helper for Toast Notifications
function showToast(icon, title) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    Toast.fire({ icon: icon, title: title });
}

// Statutory Tax & Contribution Live Calculator (PH TRAIN Law Rules)
function calculatePHStatutory(grossSalary) {
    salary = parseFloat(grossSalary) || 0;

    // SSS (2026 cap 30k MSC)
    let sssEmp = 0;
    let sssComp = 0;
    if (salary > 0) {
        const msc = Math.min(Math.max(salary, 4000), 30000);
        sssEmp = msc * 0.045; // 4.5% employee share
        sssComp = msc * 0.095; // 9.5% employer share
    }

    // PhilHealth (5% split 50-50, capped at 100k salary)
    let phEmp = 0;
    let phComp = 0;
    if (salary > 0) {
        const phSalary = Math.min(Math.max(salary, 10000), 100000);
        const totalPH = phSalary * 0.05;
        phEmp = totalPH / 2;
        phComp = totalPH / 2;
    }

    // Pag-IBIG (HDMF capped at 5k MSC = 200 max)
    let hdmfEmp = 0;
    let hdmfComp = 0;
    if (salary > 0) {
        hdmfEmp = salary >= 5000 ? 200 : salary * 0.02;
        hdmfComp = salary >= 5000 ? 200 : salary * 0.02;
    }

    // Taxable Salary after Statutory
    const totalStatutoryEmp = sssEmp + phEmp + hdmfEmp;
    const taxableIncome = Math.max(0, salary - totalStatutoryEmp);

    // BIR TRAIN Law Monthly Income Tax Brackets
    let birTax = 0;
    if (taxableIncome <= 20833) {
        birTax = 0;
    } else if (taxableIncome <= 33333) {
        birTax = (taxableIncome - 20833) * 0.15;
    } else if (taxableIncome <= 66667) {
        birTax = 1875 + (taxableIncome - 33333) * 0.20;
    } else if (taxableIncome <= 166667) {
        birTax = 8541.67 + (taxableIncome - 66667) * 0.25;
    } else if (taxableIncome <= 666667) {
        birTax = 33541.67 + (taxableIncome - 166667) * 0.30;
    } else {
        birTax = 183541.67 + (taxableIncome - 666667) * 0.35;
    }

    return {
        sssEmp: sssEmp.toFixed(2),
        sssComp: sssComp.toFixed(2),
        phEmp: phEmp.toFixed(2),
        phComp: phComp.toFixed(2),
        hdmfEmp: hdmfEmp.toFixed(2),
        hdmfComp: hdmfComp.toFixed(2),
        birTax: birTax.toFixed(2),
        totalDeduction: (totalStatutoryEmp + birTax).toFixed(2),
        netTakeHome: (salary - (totalStatutoryEmp + birTax)).toFixed(2)
    };
}

// 3. Global Form & Button Processing Loading Protection
if (window.jQuery) {
    $(document).ajaxStart(function() {
        $('button[type="submit"]:focus, .btn-submit:focus').addClass('disabled opacity-75');
    });

    $(document).ajaxStop(function() {
        $('button[type="submit"], .btn-submit').removeClass('disabled opacity-75');
    });
}
