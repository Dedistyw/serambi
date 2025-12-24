// File: track-visitor.js
document.addEventListener('DOMContentLoaded', function() {
    // Only track on public pages (not admin pages)
    if (!window.location.pathname.includes('/admin/')) {
        // Track visitor via AJAX
        fetch('visitor_tracker.php')
            .then(response => response.json())
            .then(data => {
                console.log('Visitor tracked successfully:', data);
            })
            .catch(error => console.error('Tracking error:', error));
    }
});
