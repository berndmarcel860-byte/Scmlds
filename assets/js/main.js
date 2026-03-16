/* ============================================================
   Scmlds – Main JavaScript
   ============================================================ */

(function () {
    'use strict';

    // ===== NAVBAR scroll effect =====
    const mainNav = document.getElementById('mainNav');
    if (mainNav) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                mainNav.classList.add('scrolled');
            } else {
                mainNav.classList.remove('scrolled');
            }
        });
    }

    // ===== Smooth scroll for anchor links =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const offset = 80;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    // ===== Counter animation =====
    function animateCounter(el, target, duration) {
        let start = 0;
        const step = target / (duration / 16);
        const timer = setInterval(() => {
            start += step;
            if (start >= target) {
                el.textContent = target.toLocaleString('de-DE');
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(start).toLocaleString('de-DE');
            }
        }, 16);
    }

    // Intersection observer for counters
    const counterEls = document.querySelectorAll('[data-counter]');
    if (counterEls.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseInt(el.getAttribute('data-counter'), 10);
                    animateCounter(el, target, 1800);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.3 });

        counterEls.forEach(el => observer.observe(el));
    }

    // ===== Simple scroll reveal (replaces AOS for no-CDN environments) =====
    const aosElements = document.querySelectorAll('[data-aos]');
    if (aosElements.length) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'none';
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        aosElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            revealObserver.observe(el);
        });
    }

    // ===== Scam Modal dynamic content =====
    const scamModal = document.getElementById('scamModal');
    if (scamModal) {
        scamModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            document.getElementById('scamModalTitle').textContent =
                trigger.getAttribute('data-scam-type') || '';
            document.getElementById('scamModalDesc').textContent =
                trigger.getAttribute('data-scam-desc') || '';
        });
    }

    // ===== Bootstrap form validation =====
    const form = document.getElementById('leadForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }

    // ===== Close mobile navbar on link click =====
    const navCollapse = document.getElementById('navContent');
    if (navCollapse) {
        document.querySelectorAll('#navContent .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
                if (bsCollapse) bsCollapse.hide();
            });
        });
    }

})();
