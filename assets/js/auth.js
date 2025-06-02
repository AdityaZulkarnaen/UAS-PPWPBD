// filepath: HireWay/assets/js/auth.js
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;

            if (!validateEmail(email)) {
                event.preventDefault();
                alert('Please enter a valid email address.');
            }

            if (password.length < 6) {
                event.preventDefault();
                alert('Password must be at least 6 characters long.');
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            const email = document.getElementById('registerEmail').value;
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!validateEmail(email)) {
                event.preventDefault();
                alert('Please enter a valid email address.');
            }

            if (password.length < 6) {
                event.preventDefault();
                alert('Password must be at least 6 characters long.');
            }

            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match.');
            }
        });
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
});