document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('navToggle');
    var mobileNav = document.getElementById('mobileNav');

    if (toggle && mobileNav) {
        toggle.addEventListener('click', function () {
            mobileNav.classList.toggle('open');
        });
    }
});