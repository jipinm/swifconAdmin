<?php if (is_logged_in()): ?>
            </main> <!-- End main content -->
        </div> <!-- End main-content-wrapper --> <!-- Add this closing div -->
    </div> <!-- End row -->
</div> <!-- End container-fluid -->
<?php else: // Not logged in (e.g. login page) ?>
    </div> <!-- End main-content-login -->
<?php endif; ?>

<?php if (is_logged_in()): ?>
<footer class="footer mt-auto py-3 bg-light border-top">
    <div class="container-fluid text-center">
        <span class="text-muted">&copy; <?php echo date('Y'); ?> Swifcon CMS. All rights reserved. Designed by <a href="https://www.swifcon.com" target="_blank">Swifcon</a>.</span>
    </div>
</footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> <!-- Added jQuery for potential use in File Manager & other scripts -->
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
<?php
// Page specific scripts can be included here by setting a variable in the page
if (isset($page_scripts) && is_array($page_scripts)) {
    foreach ($page_scripts as $script) {
        echo '<script src="' . htmlspecialchars($script) . '"></script>' . "\n";
    }
}
?>
</body>
</html>
