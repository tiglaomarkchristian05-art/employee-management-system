</div>

<footer id="footer" class="dashboard-footer">
    <div>&copy; <?= date('Y'); ?> <strong>Core 3 HRMS</strong>. Employee Development, Compliance &amp; Benefits.</div>
    <div class="d-flex gap-3 text-muted">
        <span>Privacy</span><span>Help Center</span><span>Support</span>
    </div>
</footer>

<!-- Shared logout confirmation: the existing logout route is called only after confirmation. -->
<div class="modal fade logout-confirm-modal" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="logout-modal-close" data-bs-dismiss="modal" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="logout-modal-icon"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
            <h3 id="logoutConfirmTitle">Sign Out of Session?</h3>
            <p>Are you sure you want to log out of your<br>current administration session?</p>
            <div class="logout-modal-actions">
                <button type="button" class="logout-cancel-btn" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="index.php?page=logout" class="m-0">
                    <?= csrf_input(); ?>
                    <button type="submit" class="logout-confirm-btn border-0">Sign Out</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="assets/js/app.js"></script>
<script src="assets/js/charts.js"></script>
</body>
</html>
