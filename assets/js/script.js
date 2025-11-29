// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuBtn && navLinks) {
        menuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (navLinks && !event.target.closest('.main-nav')) {
            navLinks.classList.remove('active');
        }
    });
    
    // Real-time Clock
    function updateTime() {
        const now = new Date();
        const timeElement = document.getElementById('currentTime');
        if (timeElement) {
            timeElement.textContent = now.toLocaleTimeString('id-ID');
        }
    }
    
    // Update time immediately and every second
    updateTime();
    setInterval(updateTime, 1000);
    
    // Auto-hide mobile menu on resize
    window.addEventListener('resize', function() {
        if (navLinks && window.innerWidth > 768) {
            navLinks.classList.remove('active');
        }
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Auto-generate WhatsApp link
    const phoneInput = document.querySelector('input[name="telepon"]');
    const waLinkInput = document.querySelector('input[name="wa_link"]');
    
    if (phoneInput && waLinkInput) {
        phoneInput.addEventListener('blur', function() {
            const phone = this.value.trim();
            
            if(phone && !waLinkInput.value) {
                let cleanPhone = phone.replace(/[^0-9]/g, '');
                if(cleanPhone.startsWith('0')) {
                    cleanPhone = '62' + cleanPhone.substring(1);
                } else if(cleanPhone.startsWith('8')) {
                    cleanPhone = '62' + cleanPhone;
                }
                waLinkInput.value = 'https://wa.me/' + cleanPhone;
            }
        });
    }
    
    // Auto-close success/error messages after 5 seconds
    setTimeout(function() {
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(message => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s';
            setTimeout(() => message.remove(), 500);
        });
    }, 5000);
});

// Admin sidebar toggle
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}
