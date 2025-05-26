// Force light mode by removing the 'dark' class from the HTML element
document.addEventListener('DOMContentLoaded', function() {
    document.documentElement.classList.remove('dark');

    // Store the preference in localStorage to persist across page loads
    localStorage.setItem('theme', 'light');
});
