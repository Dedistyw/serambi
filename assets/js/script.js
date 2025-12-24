/**
 * SERAMBI - Public JavaScript
 * Fungsi tambahan untuk halaman publik
 */

// Scroll to top functionality
(function() {
    // Create scroll to top button
    const scrollBtn = document.createElement('div');
    scrollBtn.className = 'scroll-to-top';
    scrollBtn.innerHTML = '↑';
    scrollBtn.title = 'Kembali ke atas';
    document.body.appendChild(scrollBtn);
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollBtn.style.display = 'flex';
        } else {
            scrollBtn.style.display = 'none';
        }
    });
    
    // Scroll to top when clicked
    scrollBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Skip to content for accessibility
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-to-content';
    skipLink.textContent = 'Loncat ke konten utama';
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    // Add ID to main content if not exists
    const mainContent = document.querySelector('.container') || document.querySelector('main');
    if (mainContent && !mainContent.id) {
        mainContent.id = 'main-content';
    }
})();

// Image lazy loading
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
});

// Offline detection
window.addEventListener('online', function() {
    showNotification('Koneksi internet telah pulih', 'success');
});

window.addEventListener('offline', function() {
    showNotification('Anda sedang offline. Beberapa fitur mungkin tidak tersedia.', 'warning');
});

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#2E8B57' : type === 'warning' ? '#f39c12' : '#3498db'};
        color: white;
        border-radius: 5px;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: fadeInUp 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 5000);
}

// Copy to clipboard for contact info
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('copy-contact')) {
        const text = e.target.getAttribute('data-text');
        if (text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Kontak berhasil disalin', 'success');
            }).catch(() => {
                showNotification('Gagal menyalin kontak', 'warning');
            });
        }
    }
});

// Prayer time calculation helper
function getNextPrayerTime() {
    const now = new Date();
    const currentTime = now.getHours() * 60 + now.getMinutes(); // Convert to minutes
    
    const prayerTimes = [];
    document.querySelectorAll('.jadwal-item').forEach(item => {
        const timeText = item.querySelector('.waktu').textContent;
        const [hours, minutes] = timeText.split(':').map(Number);
        const timeInMinutes = hours * 60 + minutes;
        
        prayerTimes.push({
            element: item,
            timeInMinutes: timeInMinutes,
            name: item.querySelector('.sholat-name').textContent
        });
    });
    
    // Sort by time
    prayerTimes.sort((a, b) => a.timeInMinutes - b.timeInMinutes);
    
    // Find next prayer
    let nextPrayer = null;
    for (const prayer of prayerTimes) {
        if (prayer.timeInMinutes > currentTime) {
            nextPrayer = prayer;
            break;
        }
    }
    
    // If no prayer found (it's after Isha), use first prayer of next day
    if (!nextPrayer && prayerTimes.length > 0) {
        nextPrayer = prayerTimes[0];
    }
    
    return nextPrayer;
}

// Update next prayer countdown
function updatePrayerCountdown() {
    const nextPrayer = getNextPrayerTime();
    if (!nextPrayer) return;
    
    const now = new Date();
    const currentTime = now.getHours() * 60 + now.getMinutes();
    
    let minutesRemaining = nextPrayer.timeInMinutes - currentTime;
    if (minutesRemaining < 0) {
        minutesRemaining += 24 * 60; // Add 24 hours if negative
    }
    
    const hours = Math.floor(minutesRemaining / 60);
    const minutes = minutesRemaining % 60;
    
    // You can display this countdown somewhere
    const countdownElement = document.getElementById('prayerCountdown');
    if (countdownElement) {
        countdownElement.textContent = `${nextPrayer.name}: ${hours}j ${minutes}m`;
    }
}

// Initialize countdown if element exists
if (document.getElementById('prayerCountdown')) {
    updatePrayerCountdown();
    setInterval(updatePrayerCountdown, 60000); // Update every minute
}

// Keyboard navigation for gallery
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageModal');
    if (modal && modal.style.display === 'block') {
        if (e.key === 'Escape') {
            closeModal();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            openModal(); // Update modal with new slide
        } else if (e.key === 'ArrowLeft') {
            // Previous slide logic would need to be implemented
        }
    }
});

// Service Worker registration for PWA (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js').then(function(registration) {
            console.log('ServiceWorker registration successful with scope: ', registration.scope);
        }, function(err) {
            console.log('ServiceWorker registration failed: ', err);
        });
    });
}

// Print functionality
function printPage() {
    window.print();
}

// Share functionality
function sharePage() {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            text: 'Informasi Masjid ' + document.querySelector('.masjid-name').textContent,
            url: window.location.href
        });
    } else {
        // Fallback: copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            showNotification('URL berhasil disalin', 'success');
        });
    }
}

// Initialize tooltips
document.querySelectorAll('[title]').forEach(el => {
    const title = el.getAttribute('title');
    if (title) {
        el.setAttribute('aria-label', title);
    }
});

// Add loading state to buttons
document.addEventListener('submit', function(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = '<span class="loading"></span> Memproses...';
        submitBtn.disabled = true;
    }
});
