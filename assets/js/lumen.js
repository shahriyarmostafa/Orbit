/**
 * Orbit Interactive Lumen Spotlight Engine
 * Creates a smooth hardware-accelerated ambient spotlight that follows the user cursor.
 */

(function() {
    // Only initialize on desktop pointer devices
    if (window.matchMedia('(pointer: coarse)').matches) return;

    // Create spotlight element
    const spotlight = document.createElement('div');
    spotlight.className = 'cursor-lumen-spotlight';
    spotlight.setAttribute('aria-hidden', 'true');
    document.body.appendChild(spotlight);

    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let currentX = mouseX;
    let currentY = mouseY;
    let isVisible = false;

    // Track mouse position
    window.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
        if (!isVisible) {
            spotlight.style.opacity = '1';
            isVisible = true;
        }
    });

    window.addEventListener('mouseleave', () => {
        spotlight.style.opacity = '0';
        isVisible = false;
    });

    // Smooth Lerp animation loop (smooth trailing fluid feel)
    function animate() {
        // Interpolate current towards target with 0.15 factor
        currentX += (mouseX - currentX) * 0.14;
        currentY += (mouseY - currentY) * 0.14;

        spotlight.style.transform = `translate3d(${currentX}px, ${currentY}px, 0) translate(-50%, -50%)`;
        requestAnimationFrame(animate);
    }

    animate();
})();
