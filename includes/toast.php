<!-- includes/toast.php -->
<!-- Toast Notification -->
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['success_msg']) || !empty($_SESSION['error_msg'])): ?>
<div aria-live="polite" aria-atomic="true" class="position-relative">
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <?php if (!empty($_SESSION['success_msg'])): ?>
      <div id="successToast" class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?= htmlspecialchars($_SESSION['success_msg']) ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php unset($_SESSION['success_msg']); endif; ?>
    <?php if (!empty($_SESSION['error_msg'])): ?>
      <div id="errorToast" class="toast align-items-center text-bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?= htmlspecialchars($_SESSION['error_msg']) ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php unset($_SESSION['error_msg']); endif; ?>
  </div>
</div>
<script>
window.addEventListener('load', function() {
  // Ensure Bootstrap is loaded
  if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
    document.querySelectorAll('.toast').forEach(function(toastEl) {
      var toast = new bootstrap.Toast(toastEl, { delay: 5000, autohide: true });
      toast.show();
    });
  }
});
</script>
<?php endif; ?>