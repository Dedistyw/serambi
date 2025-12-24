<?php
/**
 * Footer untuk semua halaman admin
 */
?>
            </main>
            
            <footer class="admin-footer">
                <div class="footer-content">
                    <div class="footer-left">
                        <span class="masjid-name"><?php echo htmlspecialchars(getConstant('SITE_NAME', 'Masjid')); ?></span>
                        <span class="footer-separator">•</span>
                        <span class="footer-version">SERAMBI v<?php echo htmlspecialchars(getConstant('APP_VERSION', '1.0.0')); ?></span>
                    </div>
                    <div class="footer-right">
                        <span class="footer-developer">
                            <?php echo htmlspecialchars(getConstant('DEVELOPER_NAME', 'by hasan dan para muslim')); ?>
                        </span>
                        <span class="footer-separator">•</span>
                        <span class="footer-time" id="adminTime"><?php echo date('H:i:s'); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
        // Update waktu footer
        function updateAdminTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', {hour12: false});
            document.getElementById('adminTime').textContent = timeStr;
        }
        setInterval(updateAdminTime, 1000);
        
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.admin-wrapper').classList.toggle('sidebar-collapsed');
        });
        
        // Dropdown user menu
        document.querySelector('.dropdown-toggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelector('.dropdown-menu').classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            document.querySelector('.dropdown-menu').classList.remove('show');
        });
        
        // Prevent dropdown close when clicking inside
        document.querySelector('.dropdown-menu').addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
    
    <!-- Script tambahan per halaman -->
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
    
</body>
</html>
