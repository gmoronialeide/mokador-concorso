const burgerToggle = document.getElementById('burger-toggle');
const mainNav = document.getElementById('main-nav');

burgerToggle.addEventListener('click', () => {
    burgerToggle.classList.toggle('active');
    mainNav.classList.toggle('active');
    document.body.classList.toggle('no-scroll');
});

// Close menu when clicking a link
const navLinks = mainNav.querySelectorAll('a');
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        burgerToggle.classList.remove('active');
        mainNav.classList.remove('active');
        document.body.classList.remove('no-scroll');
    });
});