/**
 * Moses Group of Companies Overseas Recruitment HRMS Main JavaScript & Component Initializer
 */

document.addEventListener('DOMContentLoaded', function () {
    // Give workflow status, detail and input dialogs one compact, consistent visual style.
    if (window.Swal && !window.Swal.__core3StatusThemeInstalled) {
        const originalSwalFire = window.Swal.fire.bind(window.Swal);
        window.Swal.fire = function (...args) {
            let options = args[0];
            if (typeof options === 'string') options = { title: options, text: args[1], icon: args[2] };
            if (!options || typeof options !== 'object') return originalSwalFire(...args);
            const classes = options.customClass && typeof options.customClass === 'object' ? options.customClass : {};
            const addClass = (current, extra) => [current, extra].filter(Boolean).join(' ');

            if (Boolean(options.input) && !options.toast) {
                const destructive = /dismiss|delete|remove|reject/i.test(options.title || options.confirmButtonText || '');
                return originalSwalFire({...options,icon:options.icon||'info',iconHtml:options.iconHtml||'<i class="fa-solid fa-pen-to-square"></i>',showCancelButton:options.showCancelButton??true,showCloseButton:options.showCloseButton??true,reverseButtons:true,buttonsStyling:false,confirmButtonText:options.confirmButtonText||'Submit',cancelButtonText:options.cancelButtonText||'Cancel',customClass:{...classes,popup:addClass(classes.popup,'core3-input-dialog'+(destructive?' core3-input-destructive':'')),icon:addClass(classes.icon,'core3-input-icon'),title:addClass(classes.title,'core3-input-title'),inputLabel:addClass(classes.inputLabel,'core3-input-label'),input:addClass(classes.input,'core3-input-control'),actions:addClass(classes.actions,'core3-input-actions'),confirmButton:addClass(classes.confirmButton,'core3-input-confirm'),cancelButton:addClass(classes.cancelButton,'core3-input-cancel'),closeButton:addClass(classes.closeButton,'core3-input-close')}});
            }

            if (Boolean(options.html) && !options.icon && !options.toast && !options.showCancelButton) {
                return originalSwalFire({...options,icon:'info',iconHtml:options.iconHtml||'<i class="fa-regular fa-eye"></i>',showCloseButton:options.showCloseButton??true,buttonsStyling:false,confirmButtonText:options.confirmButtonText||'Close',customClass:{...classes,popup:addClass(classes.popup,'core3-detail-dialog'),icon:addClass(classes.icon,'core3-detail-icon'),title:addClass(classes.title,'core3-detail-title'),htmlContainer:addClass(classes.htmlContainer,'core3-detail-copy'),actions:addClass(classes.actions,'core3-detail-actions'),confirmButton:addClass(classes.confirmButton,'core3-detail-confirm'),closeButton:addClass(classes.closeButton,'core3-detail-close')}});
            }

            const statusIconHtml={success:'<i class="fa-solid fa-check"></i>',error:'<i class="fa-solid fa-xmark"></i>',warning:'<i class="fa-solid fa-exclamation"></i>',info:'<i class="fa-solid fa-info"></i>'};
            if (!Object.hasOwn(statusIconHtml,options.icon)||options.toast||options.showCancelButton) return originalSwalFire(...args);
            return originalSwalFire({...options,iconHtml:options.iconHtml||statusIconHtml[options.icon],showCloseButton:options.showCloseButton??true,buttonsStyling:false,confirmButtonText:options.confirmButtonText||'OK',customClass:{...classes,popup:addClass(classes.popup,'core3-status-dialog core3-status-'+options.icon),icon:addClass(classes.icon,'core3-status-icon'),title:addClass(classes.title,'core3-status-title'),htmlContainer:addClass(classes.htmlContainer,'core3-status-copy'),actions:addClass(classes.actions,'core3-status-actions'),confirmButton:addClass(classes.confirmButton,'core3-status-confirm'),closeButton:addClass(classes.closeButton,'core3-status-close')}});
        };
        window.Swal.__core3StatusThemeInstalled = true;
    }
    // 1. Sidebar Collapse / Expand Toggle
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const sidebar = document.getElementById('sidebar');
    const navbar = document.getElementById('navbar');
    const mainContent = document.getElementById('main-content');
    const footer = document.querySelector('footer');

    const backdrop = document.getElementById('sidebar-backdrop');
    const mobileQuery = window.matchMedia('(max-width: 900px)');

    // Restore saved desktop sidebar state without flashing the wrong mobile state.
    const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    if (isCollapsed && !mobileQuery.matches) {
        applySidebarState(true);
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function () {
            if (mobileQuery.matches) {
                const open = document.body.classList.toggle('mobile-sidebar-open');
                this.setAttribute('aria-expanded', String(open));
                return;
            }
            const newState = !sidebar.classList.contains('sidebar-collapsed');
            applySidebarState(newState);
            localStorage.setItem('sidebar_collapsed', String(newState));
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            document.body.classList.remove('mobile-sidebar-open');
            if (sidebarToggleBtn) sidebarToggleBtn.setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('#sidebar a').forEach(link => link.addEventListener('click', function () {
        if (mobileQuery.matches) document.body.classList.remove('mobile-sidebar-open');
    }));

    function applySidebarState(collapsed) {
        if (!sidebar) return;
        const action = collapsed ? 'add' : 'remove';
        sidebar.classList[action]('sidebar-collapsed');
        if (navbar) navbar.classList[action]('sidebar-collapsed');
        if (mainContent) mainContent.classList[action]('sidebar-collapsed');
        if (footer) footer.classList[action]('sidebar-collapsed');
        document.body.classList[action]('sidebar-collapsed');
        if (sidebarToggleBtn) {
            sidebarToggleBtn.setAttribute('aria-expanded', String(!collapsed));
            const icon = sidebarToggleBtn.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = collapsed ? 'menu' : 'menu_open';
        }
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
    // Shared sidebar routes may target different sections on the same page.
    const requestedNav=new URLSearchParams(window.location.search).get('nav');
    const sidebarDestinations={
        training_management:['#trainingManagementSection'],training_calendar:['#trainingCalendarSection'],training_records:['#trainingRecordsSection'],certificates:['#certificatesSection'],my_trainings:['#trainingManagementSection'],my_certificates:['#certificatesSection'],
        document_management:['#documentTypesSection'],document_review_queue:['#documentRepositorySection'],expiring_documents:['#expiringDocumentsSection'],
        government_records:['#governmentRecordsSection'],government_information:['#governmentRecordsSection'],contribution_management:['#contributionRecordsSection'],contribution_history:['#contributionRecordsSection'],compliance_monitoring:['#governmentRecordsSection'],correction_requests:['#correctionRequestsSection'],my_corrections:['#correctionRequestsSection'],compliance_reports:['#complianceReportsSection'],
        benefit_management:['#benefitPlansSection'],available_benefits:['#benefitPlansSection'],benefit_applications:['#benefitApplicationsSection'],my_benefit_applications:['#benefitApplicationsSection'],
        loan_management:['#loanProgramsSection'],loan_applications:['#loanApplicationsSection'],my_loans:['#loanApplicationsSection'],
        separation_requests:['#separationRecordsSection'],my_separation:['#separationRecordsSection'],exit_clearance:['#separationRecordsSection','#separationTable','Processing|Clearance Ongoing'],my_exit_clearance:['#separationRecordsSection','#separationTable','Processing|Clearance Ongoing|Completed'],exit_interviews:['#separationRecordsSection','#separationTable','Clearance Ongoing|Completed'],separated_employees:['#separationRecordsSection','#separationTable','Completed']
    };
    if(requestedNav&&sidebarDestinations[requestedNav])window.setTimeout(function(){const [targetSelector,tableSelector,filter]=sidebarDestinations[requestedNav],target=document.querySelector(targetSelector);if(tableSelector&&filter&&window.jQuery&&$.fn.DataTable){const table=document.querySelector(tableSelector);if(table&&$.fn.DataTable.isDataTable(table))$(table).DataTable().search(filter,true,false).draw()}if(target){target.classList.add('sidebar-destination-active');target.scrollIntoView({behavior:'smooth',block:'start'})}},100);
});

// Helper for SweetAlert Confirm Dialog
function confirmAction(title, text, confirmBtnText, callback) {
    Swal.fire({
        title: title || 'Are you sure?',
        text: text || "You won't be able to revert this action!",
        icon: 'warning',
        showCancelButton: true,
        showCloseButton: true,
        reverseButtons: true,
        buttonsStyling: false,
        confirmButtonText: confirmBtnText || 'Yes, proceed!',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'core3-confirm-dialog',
            icon: 'core3-confirm-icon',
            title: 'core3-confirm-title',
            htmlContainer: 'core3-confirm-copy',
            actions: 'core3-confirm-actions',
            confirmButton: 'core3-confirm-primary',
            cancelButton: 'core3-confirm-cancel',
            closeButton: 'core3-confirm-close'
        }
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

// Helper for Toast Notifications
function showErrorDialog(message, title) {
    return Swal.fire({
        icon:'error',
        iconHtml:'<i class="fa-solid fa-xmark" aria-hidden="true"></i>',
        title:title||'Error',
        text:message||'The request could not be completed.',
        showCloseButton:true,
        buttonsStyling:false,
        confirmButtonText:'OK',
        customClass:{popup:'core3-error-dialog',icon:'core3-error-icon',title:'core3-error-title',htmlContainer:'core3-error-copy',actions:'core3-error-actions',confirmButton:'core3-error-confirm',closeButton:'core3-error-close'}
    });
}

// Helper for Toast Notifications
function showToast(icon,title){
    const Toast=Swal.mixin({toast:true,position:'top-end',backdrop:false,showConfirmButton:false,showCloseButton:true,timer:3200,timerProgressBar:false,showClass:{popup:'core3-toast-in'},hideClass:{popup:'core3-toast-out'},customClass:{container:'core3-toast-container',popup:'core3-toast',icon:'core3-toast-icon',title:'core3-toast-title',closeButton:'core3-toast-close',timerProgressBar:'core3-toast-progress'},didOpen:(toast)=>{toast.classList.add('core3-toast-'+icon);toast.addEventListener('mouseenter',Swal.stopTimer);toast.addEventListener('mouseleave',Swal.resumeTimer)}});
    const icons={success:'fa-check',error:'fa-xmark',warning:'fa-exclamation',info:'fa-info'};
    Toast.fire({icon,iconHtml:'<i class="fa-solid '+(icons[icon]||'fa-bell')+'" aria-hidden="true"></i>',title});
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
