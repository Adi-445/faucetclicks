// js/main.js
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication status
    checkAuthStatus();
    
    // Event listeners
    document.getElementById('loginBtn')?.addEventListener('click', () => {
        window.location.href = 'auth.html';
    });
    
    document.getElementById('getStartedBtn')?.addEventListener('click', () => {
        window.location.href = 'auth.html';
    });
    
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('darkMode', isDark);
        });
    }
    
    // Initialize theme based on localStorage or system preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const savedDarkMode = localStorage.getItem('darkMode') === 'true';
    const shouldDarkMode = savedDarkMode !== null ? savedDarkMode : prefersDark;
    
    if (shouldDarkMode) {
        document.documentElement.classList.add('dark');
    }
});

function checkAuthStatus() {
    // Check if user is authenticated by verifying token
    fetch('api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'status' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.authenticated) {
            window.location.href = 'dashboard.html';
        }
    })
    .catch(console.error);
}
