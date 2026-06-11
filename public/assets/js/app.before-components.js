        let currentPage = null;
        let settings = {};

        async function loadSettings() {
            const res = await fetch('/api/settings');
            settings = await res.json();
            document.documentElement.style.setProperty('--primary-color', settings.primary_color || '#ff6600');
            document.body.style.fontFamily = settings.font_family || 'Inter, sans-serif';
            document.getElementById('siteTitle').innerText = settings.site_title || 'GuruFix';
            if(settings.meta_description) document.getElementById('siteDescription').innerText = settings.meta_description;
            document.title = settings.site_title || 'GuruFix';
            if(settings.logo_url) {
                const logoBlock = document.getElementById('logoBlock');
                logoBlock.innerHTML = `<img src="${settings.logo_url}" class="logo-img" loading="lazy">`;
            }
        }

        async function renderPage(slug) {
            const contentDiv = document.getElementById('mainContent');
            if (!slug || slug === 'home') {
                // Главная страница
                const services = await fetch('/api/services').then(r=>r.json());
                const reviews = await fetch('/api/reviews').then(r=>r.json());
                let html = `
                    <section class="mb-5 fade-up"><h2 class="text-center mb-4">Наши услуги</h2><div class="row" id="servicesList"></div></section>
                    <section class="mb-5 fade-up" style="animation-delay: 0.2s;"><h2 class="text-center mb-4">Отзывы клиентов</h2><div class="row" id="reviewsList"></div></section>
                    <section class="lead-form p-4 p-md-5 mb-5 fade-up" style="animation-delay: 0.3s;">
                        <h3 class="mb-4 text-center"><i class="bi bi-chat-right-text"></i> Оставить заявку</h3>
                        <form id="leadForm">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Ваше имя <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Телефон <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Услуга</label>
                                    <select name="service_id" class="form-select">
                                        <option value="">Выберите услугу</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Сообщение</label>
                                    <textarea name="message" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary px-5 py-2">Отправить заявку <i class="bi bi-arrow-right"></i></button>
                                </div>
                            </div>
                        </form>
                        <div id="formMessage" class="mt-4"></div>
                    </section>
                `;
                contentDiv.innerHTML = html;
                // Заполняем услуги
                const servicesContainer = document.getElementById('servicesList');
                servicesContainer.innerHTML = services.map(s => `
                    <div class="col-md-4 mb-4">
                        <div class="card service-card h-100 text-center">
                            ${s.icon ? `<img src="${s.icon}" class="service-icon mx-auto mt-3" loading="lazy">` : '<i class="bi bi-tools display-1 mt-3" style="color: var(--primary-color);"></i>'}
                            <div class="card-body">
                                <h5 class="card-title">${s.title}</h5>
                                <p class="card-text">${s.description}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                const select = document.querySelector('select[name="service_id"]');
                select.innerHTML = '<option value="">Выберите услугу</option>' + services.map(s => `<option value="${s.id}">${s.title}</option>`).join('');
                // Отзывы
                const reviewsContainer = document.getElementById('reviewsList');
                if(reviews.length) {
                    reviewsContainer.innerHTML = reviews.map(r => `
                        <div class="col-md-6 mb-4">
                            <div class="review-card p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-person-circle fs-2 me-2" style="color: var(--primary-color);"></i>
                                    <strong class="me-auto">${r.author}</strong>
                                    <span class="text-warning">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</span>
                                </div>
                                <p class="mb-0">${r.text}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    reviewsContainer.innerHTML = '<p class="text-center text-muted">Пока нет отзывов. Будьте первым!</p>';
                }
                attachLeadForm();
            } else {
                // Страница из БД
                const res = await fetch(`/api/page?slug=${encodeURIComponent(slug)}`);
                if (res.status === 404) {
                    contentDiv.innerHTML = '<div class="alert alert-danger text-center">Страница не найдена</div>';
                    return;
                }
                const page = await res.json();
                contentDiv.innerHTML = `
                    <div class="card p-4 p-md-5 fade-up" style="border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                        <h1 class="mb-4">${page.title}</h1>
                        <div class="content">${page.content}</div>
                    </div>
                `;
                document.title = page.title + ' | ' + (settings.site_title || 'GuruFix');
                if(page.meta_description) document.querySelector('meta[name="description"]').setAttribute('content', page.meta_description);
            }
        }

        function attachLeadForm() {
            const form = document.getElementById('leadForm');
            if(!form) return;
            form.addEventListener('submit', async(e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const payload = { name: fd.get('name'), phone: fd.get('phone'), service_id: fd.get('service_id') || null, message: fd.get('message') };
                try {
                    const res = await fetch('/api/leads', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                    const data = await res.json();
                    const msgDiv = document.getElementById('formMessage');
                    if(res.ok) {
                        msgDiv.innerHTML = `<div class="alert alert-success">${data.message || 'Заявка отправлена!'}</div>`;
                        form.reset();
                    } else {
                        msgDiv.innerHTML = `<div class="alert alert-danger">${data.error || 'Ошибка'}</div>`;
                    }
                    setTimeout(() => msgDiv.innerHTML = '', 5000);
                } catch (err) {
                    console.error(err);
                }
            });
        }

        async function init() {
            await loadSettings();
            const urlParams = new URLSearchParams(window.location.search);
            const pageSlug = urlParams.get('page');
            currentPage = pageSlug ? pageSlug : 'home';
            renderPage(currentPage);
        }
        init();
