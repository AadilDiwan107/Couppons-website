// Create floating particles
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    const particleCount = 100; // Increased particle count for more visual effect
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Random size
        const size = Math.random() * 8 + 5;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        
        // Random position
        particle.style.left = `${Math.random() * 100}%`;
        particle.style.top = `${Math.random() * 100}%`;
        
        // Random animation duration and delay
        const duration = Math.random() * 12 + 6;
        const delay = Math.random() * 4.5;
        particle.style.animationDuration = `${duration}s`;
        particle.style.animationDelay = `${delay}s`;
        
        particlesContainer.appendChild(particle);
    }
}

// Initialize particles when page loads
window.addEventListener('load', createParticles);

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            // Show a temporary notification
            const originalText = document.querySelector('.coupon-code').textContent;
            document.querySelector('.coupon-code').textContent = 'Copied!';
            setTimeout(function() {
                document.querySelector('.coupon-code').textContent = originalText;
            }, 2000);
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
}

function markAsUsed(couponId, button) {
    // Change button appearance to show it's been used
    button.classList.add('used');
    button.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Used!';
    button.onclick = null; // Remove the click handler
    
    // Change card appearance to show it's used
    const card = button.closest('.coupon-card');
    card.classList.remove('active');
    card.classList.add('used');
    
    // Update the coupon code display
    const codeContainer = card.querySelector('.coupon-code');
    if (codeContainer) {
        codeContainer.style.background = 'linear-gradient(45deg, #28a745, #218838)';
        codeContainer.innerHTML = '<i class="bi bi-check-circle me-2"></i>USED';
    }
    
    // Add status badge
    const statusBadge = document.createElement('div');
    statusBadge.className = 'status-badge';
    statusBadge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Used';
    card.appendChild(statusBadge);
    
    // Send AJAX request to mark as used
    fetch('mark_used.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            coupon_id: couponId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Coupon marked as used');
        } else {
            console.error('Failed to mark coupon as used');
            // Revert changes if failed
            button.classList.remove('used');
            button.innerHTML = '<i class="bi bi-check-circle me-2"></i>I Used It';
            button.onclick = function() { markAsUsed(couponId, this); };
            
            card.classList.remove('used');
            card.classList.add('active');
            
            if (codeContainer) {
                codeContainer.style.background = 'linear-gradient(45deg, #28a745, #218838)';
                codeContainer.innerHTML = card.querySelector('.coupon-code').dataset.originalCode || 'Code';
            }
            
            // Remove status badge
            const badge = card.querySelector('.status-badge');
            if (badge) badge.remove();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revert changes if error
        button.classList.remove('used');
        button.innerHTML = '<i class="bi bi-check-circle me-2"></i>I Used It';
        button.onclick = function() { markAsUsed(couponId, this); };
        
        card.classList.remove('used');
        card.classList.add('active');
        
        if (codeContainer) {
            codeContainer.style.background = 'linear-gradient(45deg, #28a745, #218838)';
            codeContainer.innerHTML = card.querySelector('.coupon-code').dataset.originalCode || 'Code';
        }
        
        // Remove status badge
        const badge = card.querySelector('.status-badge');
        if (badge) badge.remove();
    });
}
