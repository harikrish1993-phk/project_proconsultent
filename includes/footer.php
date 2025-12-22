</div> <!-- End content-wrapper -->

<footer class="footer bg-light text-center py-3">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All rights reserved. Version <?php echo APP_VERSION; ?>.</p>
    </div>
</footer>

<!-- Global JS -->
<script src="assets/vendor/libs/jquery/jquery.js"></script>
<script src="assets/vendor/js/bootstrap.js"></script>
<script src="assets/js/main.js"></script>

<!-- Error Handler -->
<script>
window.addEventListener('error', function(e) {
    alert('An error occurred: ' + e.message + '. Please try refreshing the page.');
});
</script>
</body>
</html>