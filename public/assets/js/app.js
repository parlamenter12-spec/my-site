let currentPage = null;
let settings = {};

// =========================
// ESCAPE HTML (XSS PROTECTION)
// =========================
function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// =========================
// LOADER
// =========================

function showPageLoader() {

    const content =
        document.getElementById('mainContent');

    if (!content) return;

    content.innerHTML = `

        <div class="page-loader">

            <div class="loader-card shimmer"></div>

            <div class="loader-grid">

                <div class="loader-item shimmer"></div>
                <div class="loader-item shimmer"></div>
                <div class="loader-item shimmer"></div>

            </div>

        </div>

    `;
}

function animatePageIn() {

    const content =
        document.getElementById('mainContent');

    if (!content) return;

    content.classList.remove('page-enter');

    void content.offsetWidth;

    content.classList.add('page-enter');
}

// =========================
// COMPONENTS
// =========================

async function loadComponent(id, path) {

    try {

        const response = await fetch(path);

        const html = await response.text();

        document.getElementById(id).innerHTML = html;

    } catch (err) {

        console.error("Component load error:", err);
    }
}

async function loadPage(path) {

    try {

        const response = await fetch(path);

        return await response.text();

    } catch (err) {

        return `
            <div class="alert alert-danger">
                Ошибка загрузки страницы
            </div>
        `;
    }
}

// =========================
// SETTINGS
// =========================

async function loadSettings() {

    const res = await fetch('/api/settings');

    settings = await res.json();

    document.documentElement.style.setProperty(
        '--primary-color',
        settings.primary_color || '#ff6600'
    );

    document.body.style.fontFamily =
        settings.font_family || 'Inter, sans-serif';

    const siteTitle =
        document.getElementById('siteTitle');

    if (siteTitle) {

        siteTitle.innerText =
            settings.site_title || 'GuruFix';
    }

    if (settings.meta_description) {

        const desc =
            document.getElementById('siteDescription');

        if (desc) {

            desc.innerText =
                settings.meta_description;
        }
    }

    document.title =
        settings.site_title || 'GuruFix';

    if (settings.logo_url) {

        const logoBlock =
            document.getElementById('logoBlock');

        if (logoBlock) {

            logoBlock.innerHTML = `
                <img src="${escapeHtml(settings.logo_url)}"
                     class="logo-img"
                     loading="lazy">
            `;
        }
    }
}

// =========================
// COUNTERS
// =========================

function initCounters() {

    const counters =
        document.querySelectorAll('.counter-number');

    counters.forEach(counter => {

        const target =
            +counter.getAttribute('data-target');

        let current = 0;

        const increment =
            target / 80;

        const updateCounter = () => {

            current += increment;

            if (current < target) {

                counter.innerText =
                    Math.floor(current);

                requestAnimationFrame(updateCounter);

            } else {

                counter.innerText = target;
            }
        };

        updateCounter();
    });
}

// =========================
// SCROLL ANIMATIONS
// =========================

function initScrollAnimations() {

    const observer =
        new IntersectionObserver((entries) => {

            entries.forEach(entry => {

                if (entry.isIntersecting) {

                    entry.target.classList.add('show-element');
                }
            });

        }, {
            threshold: 0.12
        });

    document
        .querySelectorAll('.reveal')
        .forEach(el => observer.observe(el));
}

// =========================
// PARALLAX HERO
// =========================

function initParallax() {

    const hero =
        document.querySelector('.hero-section');

    if (!hero) return;

    window.addEventListener('mousemove', (e) => {

        const x =
            (window.innerWidth / 2 - e.clientX) / 40;

        const y =
            (window.innerHeight / 2 - e.clientY) / 40;

        const glow =
            document.querySelector('.hero-bg-glow');

        const glass =
            document.querySelector('.hero-glass-card');

        if (glow) {

            glow.style.transform =
                `translate(${x}px, ${y}px)`;
        }

        if (glass) {

            glass.style.transform =
                `
                    rotateY(${-x}deg)
                    rotateX(${y}deg)
                    translateY(-6px)
                `;
        }
    });
}

// =========================
// MAGNETIC BUTTONS
// =========================

function initMagneticButtons() {

    const buttons =
        document.querySelectorAll(
            '.btn-primary, .hero-btn-secondary'
        );

    buttons.forEach(button => {

        button.addEventListener('mousemove', (e) => {

            const rect =
                button.getBoundingClientRect();

            const x =
                e.clientX - rect.left - rect.width / 2;

            const y =
                e.clientY - rect.top - rect.height / 2;

            button.style.transform =
                `
                    translate(${x * 0.12}px,
                    ${y * 0.12}px)
                `;
        });

        button.addEventListener('mouseleave', () => {

            button.style.transform =
                'translate(0,0)';
        });
    });
}

// =========================
// 3D CARDS
// =========================

function initTiltCards() {

    const cards =
        document.querySelectorAll(
            '.service-card-modern, .review-card'
        );

    cards.forEach(card => {

        card.addEventListener('mousemove', (e) => {

            const rect =
                card.getBoundingClientRect();

            const x =
                e.clientX - rect.left;

            const y =
                e.clientY - rect.top;

            const rotateY =
                ((x / rect.width) - 0.5) * 10;

            const rotateX =
                ((y / rect.height) - 0.5) * -10;

            card.style.transform =
                `
                    perspective(1000px)
                    rotateY(${rotateY}deg)
                    rotateX(${rotateX}deg)
                    translateY(-8px)
                `;
        });

        card.addEventListener('mouseleave', () => {

            card.style.transform =
                `
                    perspective(1000px)
                    rotateY(0deg)
                    rotateX(0deg)
                    translateY(0)
                `;
        });
    });
}

// =========================
// NAVBAR
// =========================

function initNavbar() {

    const navbar =
        document.getElementById('premiumNavbar');

    const burger =
        document.getElementById('burgerButton');

    const mobileMenu =
        document.getElementById('mobileMenu');

    if (!navbar) return;

    window.addEventListener('scroll', () => {

        if (window.scrollY > 30) {

            navbar.classList.add('scrolled');

        } else {

            navbar.classList.remove('scrolled');
        }
    });

    if (burger && mobileMenu) {

        burger.addEventListener('click', () => {

            burger.classList.toggle('active');

            mobileMenu.classList.toggle('active');

            document.body.classList.toggle('menu-open');
        });

        mobileMenu.querySelectorAll('a')
            .forEach(link => {

                link.addEventListener('click', () => {

                    burger.classList.remove('active');

                    mobileMenu.classList.remove('active');

                    document.body.classList.remove('menu-open');
                });
            });
    }
}

// =========================
// PAGE RENDER
// =========================

async function renderPage(slug) {

    const contentDiv =
        document.getElementById('mainContent');

    showPageLoader();

    if (!slug || slug === 'home') {

        const services =
            await fetch('/api/services')
                .then(r => r.json());

        const reviews =
            await fetch('/api/reviews')
                .then(r => r.json());

        contentDiv.innerHTML =
            await loadPage("/pages/home.html");

        document.querySelectorAll(
            '#mainContent section'
        ).forEach(section => {

            section.classList.add('reveal');
        });

        // SERVICES
        const servicesContainer =
            document.getElementById('servicesList');

        if (servicesContainer) {

            servicesContainer.innerHTML =
                services.map((s, index) => `

                    <div class="col-lg-4 col-md-6 mb-4 reveal"
                         style="transition-delay:${index * 0.08}s;">

                        <div class="service-card-modern">

                            <div class="service-card-glow"></div>

                            <div class="service-icon-wrap">

                                ${s.icon
                                    ? `
                                        <img src="${escapeHtml(s.icon)}"
                                             class="service-icon"
                                             loading="lazy">
                                      `
                                    : `
                                        <i class="bi bi-stars service-fallback-icon"></i>
                                      `
                                }

                            </div>

                            <div class="service-content">

                                <h3 class="service-title">
                                    ${escapeHtml(s.title)}
                                </h3>

                                <p class="service-description">
                                    ${escapeHtml(s.description)}
                                </p>

                                <div class="service-action">
                                    Подробнее →
                                </div>

                            </div>

                        </div>

                    </div>

                `).join('');
        }

        // SELECT
        const select =
            document.querySelector(
                'select[name="service_id"]'
            );

        if (select) {

            select.innerHTML =
                '<option value="">Выберите услугу</option>' +

                services.map(s => `
                    <option value="${s.id}">
                        ${escapeHtml(s.title)}
                    </option>
                `).join('');
        }

        // REVIEWS
        const reviewsContainer =
            document.getElementById('reviewsList');

        if (reviewsContainer) {

            if (reviews.length) {

                reviewsContainer.innerHTML =
                    reviews.map((r, index) => `

                        <div class="col-lg-6 mb-4 reveal"
                             style="transition-delay:${index * 0.08}s;">

                            <div class="review-card p-4 h-100">

                                <div class="d-flex align-items-center mb-3">

                                    <div class="review-avatar">
                                        <i class="bi bi-person-fill"></i>
                                    </div>

                                    <div class="ms-3">

                                        <strong class="d-block">
                                            ${escapeHtml(r.author)}
                                        </strong>

                                        <span class="text-warning">
                                            ${'★'.repeat(r.rating)}
                                            ${'☆'.repeat(5 - r.rating)}
                                        </span>

                                    </div>

                                </div>

                                <p class="mb-0">
                                    ${escapeHtml(r.text)}
                                </p>

                            </div>

                        </div>

                    `).join('');

            } else {

                reviewsContainer.innerHTML = `
                    <p class="text-center text-muted">
                        Пока нет отзывов.
                    </p>
                `;
            }
        }

        attachLeadForm();

        initScrollAnimations();

        initCounters();

        initParallax();

        initMagneticButtons();

        initTiltCards();

        animatePageIn();

    } else {

        const res =
            await fetch(
                `/api/page?slug=${encodeURIComponent(slug)}`
            );

        if (res.status === 404) {

            contentDiv.innerHTML = `
                <div class="alert alert-danger text-center">
                    Страница не найдена
                </div>
            `;

            return;
        }

        const page = await res.json();

        contentDiv.innerHTML = `

            <div class="card p-4 p-md-5 reveal"
                 style="
                    border-radius: 28px;
                    border: none;
                    box-shadow:
                        0 20px 40px rgba(0,0,0,0.08);
                 ">

                <h1 class="mb-4">
                    ${escapeHtml(page.title)}
                </h1>

                <div class="content">
                    ${page.content}
                </div>

            </div>

        `;

        initScrollAnimations();

        animatePageIn();

        document.title =
            escapeHtml(page.title) +
            ' | ' +
            (settings.site_title || 'GuruFix');
    }
}

// =========================
// FORM
// =========================

function attachLeadForm() {

    const form =
        document.getElementById('leadForm');

    if (!form) return;

    form.addEventListener('submit', async (e) => {

        e.preventDefault();

        const fd = new FormData(form);

        const payload = {
            name: fd.get('name'),
            phone: fd.get('phone'),
            service_id: fd.get('service_id') || null,
            message: fd.get('message')
        };

        try {

            const res =
                await fetch('/api/leads', {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify(payload)
                });

            const data = await res.json();

            const msgDiv =
                document.getElementById('formMessage');

            if (res.ok) {

                msgDiv.innerHTML = `
                    <div class="alert alert-success">
                        ${escapeHtml(data.message || 'Заявка отправлена!')}
                    </div>
                `;

                form.reset();

            } else {

                msgDiv.innerHTML = `
                    <div class="alert alert-danger">
                        ${escapeHtml(data.error || 'Ошибка')}
                    </div>
                `;
            }

            setTimeout(() => {

                msgDiv.innerHTML = '';

            }, 5000);

        } catch (err) {

            console.error(err);
        }
    });
}

// =========================
// INIT
// =========================

async function init() {

    document.documentElement.style.scrollBehavior =
        'smooth';

    const savedTheme =
        localStorage.getItem('theme');

    if (savedTheme === 'dark') {

        document.body.classList.add('dark-mode');
    }

    document.addEventListener('click', (e) => {

        if (e.target.closest('#themeToggle')) {

            document.body.classList.toggle('dark-mode');

            localStorage.setItem(
                'theme',

                document.body.classList.contains('dark-mode')
                    ? 'dark'
                    : 'light'
            );
        }
    });

    await loadComponent(
        "heroComponent",
        "/components/hero.html"
    );

    await loadComponent(
        "footerComponent",
        "/components/footer.html"
    );

    await loadSettings();

    initNavbar();

    const urlParams =
        new URLSearchParams(window.location.search);

    const pageSlug =
        urlParams.get('page');

    currentPage =
        pageSlug
            ? pageSlug
            : 'home';

    renderPage(currentPage);
}

init();