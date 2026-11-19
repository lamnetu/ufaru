const $  = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

/* ── Шапка: высота, кондиционирование, прогресс ───────────── */
function initHeader() {
    const header = $('#site-header');
    const bar = $('#scroll-progress');
    if (!header) return;

    const sync = () => {
        document.documentElement.style.setProperty('--header-h', `${header.offsetHeight}px`);
        const y = window.scrollY;
        header.classList.toggle('is-condensed', y > 40);
        if (bar) {
            const max = document.documentElement.scrollHeight - window.innerHeight;
            bar.style.transform = `scaleX(${max > 0 ? Math.min(y / max, 1) : 0})`;
        }
    };
    sync();
    window.addEventListener('scroll', sync, { passive: true });
    window.addEventListener('resize', sync);
}

/* ── Мобильное меню ───────────────────────────────────────── */
function initMenu() {
    const btn = $('#burger'), panel = $('#mobile-menu');
    if (!btn || !panel) return;

    const set = (open) => {
        panel.classList.toggle('hidden', !open);
        btn.setAttribute('aria-expanded', String(open));
        $('#burger-open')?.classList.toggle('hidden', open);
        $('#burger-close')?.classList.toggle('hidden', !open);
    };
    set(false);

    btn.addEventListener('click', () => set(panel.classList.contains('hidden')));
    $$('a', panel).forEach(a => a.addEventListener('click', () => set(false)));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') set(false); });
}

/* ── Активный раздел в навигации ──────────────────────────── */
function initScrollSpy() {
    const links = $$('.nav-link[href^="#"]');
    if (!links.length) return;
    const map = new Map();
    links.forEach(l => {
        const sec = document.getElementById(l.getAttribute('href').slice(1));
        if (sec) map.set(sec, l);
    });
    if (!map.size) return;

    const io = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            links.forEach(l => l.classList.remove('is-active'));
            map.get(e.target)?.classList.add('is-active');
        });
    }, { rootMargin: `-${parseInt(getComputedStyle(document.documentElement).getPropertyValue('--header-h')) || 150}px 0px -65% 0px`, threshold: 0 });

    map.forEach((_, sec) => io.observe(sec));
}

/* ── Появление блоков ─────────────────────────────────────── */
function initReveal() {
    const items = $$('.reveal');
    if (!items.length) return;
    if (!('IntersectionObserver' in window)) { items.forEach(i => i.classList.add('is-visible')); return; }

    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach((e, i) => {
            if (!e.isIntersecting) return;
            setTimeout(() => e.target.classList.add('is-visible'), i * 70);
            obs.unobserve(e.target);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: .08 });

    items.forEach(i => io.observe(i));
}

/* ── Часы Уфы + статус приёмной ───────────────────────────── */
function initClock() {
    const time = $('#ufa-time'), date = $('#ufa-date'), status = $('#reception-status');
    if (!time) return;

    const tf = new Intl.DateTimeFormat('ru-RU', { timeZone: 'Asia/Yekaterinburg', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const df = new Intl.DateTimeFormat('ru-RU', { timeZone: 'Asia/Yekaterinburg', weekday: 'long', day: 'numeric', month: 'long' });
    const pf = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Yekaterinburg', weekday: 'short', hour: 'numeric', hour12: false });

    const tick = () => {
        const now = new Date();
        time.textContent = tf.format(now);
        if (date) date.textContent = df.format(now);
        if (status) {
            const parts = Object.fromEntries(pf.formatToParts(now).map(p => [p.type, p.value]));
            const h = parseInt(parts.hour, 10) % 24;
            const workday = !['Sat', 'Sun'].includes(parts.weekday);
            const open = workday && h >= 9 && h < 18;
            status.textContent = open ? 'Приёмная открыта' : 'Приёмная закрыта';
            status.dataset.open = String(open);
            status.className = open
                ? 'inline-flex items-center gap-1.5 text-green-200'
                : 'inline-flex items-center gap-1.5 text-sand-200';
        }
    };
    tick();
    setInterval(tick, 1000);
}

/* ── Доступность: масштаб и контраст ──────────────────────── */
function initA11y() {
    const root = document.documentElement;
    const saved = localStorage.getItem('ufa-scale');
    const contrast = localStorage.getItem('ufa-contrast') === '1';

    const applyScale = (v) => {
        root.style.setProperty('--font-scale', String(v));
        localStorage.setItem('ufa-scale', String(v));
        $$('[data-scale]').forEach(b => b.setAttribute('aria-pressed', String(Number(b.dataset.scale) === v)));
    };
    applyScale(saved ? Number(saved) : 1);
    root.classList.toggle('a11y', contrast);
    $('[data-contrast]')?.setAttribute('aria-pressed', String(contrast));

    $$('[data-scale]').forEach(b => b.addEventListener('click', () => applyScale(Number(b.dataset.scale))));
    $('[data-contrast]')?.addEventListener('click', (e) => {
        const on = !root.classList.contains('a11y');
        root.classList.toggle('a11y', on);
        localStorage.setItem('ufa-contrast', on ? '1' : '0');
        e.currentTarget.setAttribute('aria-pressed', String(on));
    });
}

/* ── Доска объявлений: фильтры, поиск, сортировка ─────────── */
function initBoard() {
    const grid = $('#board-grid');
    if (!grid) return;

    const cards = $$('.ad-card', grid);
    const chips = $$('#board-chips .chip');
    const district = $('#board-district');
    const search = $('#board-search');
    const sort = $('#board-sort');
    const counter = $('#board-count');
    const empty = $('#board-empty');
    const more = $('#board-more');
    const reset = $('#board-reset');

    const state = { cat: 'all', district: 'all', q: '', sort: 'date', expanded: false };

    const render = () => {
        let shown = 0;
        const q = state.q.trim().toLowerCase();

        const ordered = cards.slice().sort((a, b) => {
            if (state.sort === 'cheap') return (+a.dataset.price || 0) - (+b.dataset.price || 0);
            if (state.sort === 'expensive') return (+b.dataset.price || 0) - (+a.dataset.price || 0);
            return (+b.dataset.date || 0) - (+a.dataset.date || 0);
        });
        ordered.forEach(c => grid.appendChild(c));

        ordered.forEach(card => {
            const okCat = state.cat === 'all' || card.dataset.category === state.cat;
            const okDist = state.district === 'all' || card.dataset.district === state.district;
            const okQ = !q || card.dataset.title.includes(q) || card.dataset.text.includes(q);
            const okExtra = state.expanded || card.dataset.extra !== '1' || state.cat !== 'all' || q || state.district !== 'all';
            const visible = okCat && okDist && okQ && okExtra;
            card.classList.toggle('hidden', !visible);
            if (visible) shown++;
        });

        if (counter) counter.textContent = String(shown);
        empty?.classList.toggle('hidden', shown > 0);
        grid.classList.toggle('hidden', shown === 0);

        if (more) {
            const hiddenExtra = cards.filter(c => c.dataset.extra === '1' && c.classList.contains('hidden')).length;
            more.classList.toggle('hidden', state.expanded || hiddenExtra === 0);
        }
    };

    chips.forEach(chip => chip.addEventListener('click', () => {
        state.cat = chip.dataset.cat;
        chips.forEach(c => { c.classList.toggle('is-active', c === chip); c.setAttribute('aria-pressed', String(c === chip)); });
        render();
    }));

    district?.addEventListener('change', e => { state.district = e.target.value; render(); });
    sort?.addEventListener('change', e => { state.sort = e.target.value; render(); });

    let t;
    search?.addEventListener('input', e => {
        clearTimeout(t);
        t = setTimeout(() => { state.q = e.target.value; render(); }, 130);
    });

    more?.addEventListener('click', () => { state.expanded = true; render(); });
    reset?.addEventListener('click', () => {
        Object.assign(state, { cat: 'all', district: 'all', q: '', sort: 'date', expanded: false });
        if (search) search.value = '';
        if (district) district.value = 'all';
        if (sort) sort.value = 'date';
        chips.forEach((c, i) => { c.classList.toggle('is-active', i === 0); c.setAttribute('aria-pressed', String(i === 0)); });
        render();
    });

    render();
}

/* ── Раскрывающиеся списки ────────────────────────────────── */
function initExpanders() {
    $$('[data-expand]').forEach(btn => {
        const box = document.getElementById(btn.dataset.expand);
        if (!box) return;
        btn.addEventListener('click', () => {
            const open = box.dataset.open === '1';
            box.dataset.open = open ? '0' : '1';
            $$('[data-extra]', box).forEach(el => el.classList.toggle('hidden', open));
            btn.textContent = open ? btn.dataset.more : btn.dataset.less;
        });
    });
}

/* ── Форма обращения ──────────────────────────────────────── */
function initAppeal() {
    const form = $('#appeal-form');
    if (!form) return;

    const name = $('#ap-name'), email = $('#ap-email'), topic = $('#ap-topic'), msg = $('#ap-message');
    const file = $('#ap-file'), fileName = $('#ap-file-name'), counter = $('#ap-counter');
    const MAX = 10 * 1024 * 1024;

    const err = (input, text) => {
        const box = form.querySelector(`[data-error-for="${input.id}"]`);
        input.classList.toggle('field-error', Boolean(text));
        input.setAttribute('aria-invalid', String(Boolean(text)));
        if (box) { box.textContent = text || ''; box.classList.toggle('hidden', !text); }
        return !text;
    };

    const vName = () => err(name, name.value.trim().length >= 2 ? '' : 'Укажите имя и фамилию');
    const vEmail = () => err(email, /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.value.trim()) ? '' : 'Проверьте адрес почты');
    const vTopic = () => err(topic, topic.value ? '' : 'Выберите тему обращения');
    const vMsg = () => err(msg, msg.value.trim().length >= 20 ? '' : 'Опишите ситуацию подробнее — минимум 20 символов');

    [name, email, topic, msg].forEach(el => el?.addEventListener('blur', () => ({ 'ap-name': vName, 'ap-email': vEmail, 'ap-topic': vTopic, 'ap-message': vMsg })[el.id]()));

    msg?.addEventListener('input', () => {
        const n = msg.value.length;
        if (counter) {
            counter.textContent = `${n} / 2000`;
            counter.className = n > 2000 ? 'text-xs font-semibold text-clay-600' : 'text-xs text-ink-soft';
        }
        if (n >= 20) err(msg, '');
    });

    file?.addEventListener('change', () => {
        const f = file.files?.[0];
        if (!f) { fileName.textContent = 'Файл не выбран'; err(file, ''); return; }
        if (f.size > MAX) {
            fileName.textContent = '';
            err(file, `Файл ${(f.size / 1048576).toFixed(1)} МБ — максимум 10 МБ`);
            file.value = '';
            return;
        }
        err(file, '');
        fileName.textContent = `${f.name} · ${(f.size / 1024).toFixed(0)} КБ`;
    });

    form.addEventListener('submit', e => {
        const ok = [vName(), vEmail(), vTopic(), vMsg()].every(Boolean);
        if (msg.value.length > 2000) { err(msg, 'Не более 2000 символов'); e.preventDefault(); return; }
        if (!ok) {
            e.preventDefault();
            form.querySelector('.field-error')?.focus();
            return;
        }
        const btn = $('#ap-submit');
        if (btn) { btn.disabled = true; btn.textContent = 'Отправляем…'; }
    });
}

/* ── Копирование ссылки на новость ────────────────────────── */
function initShare() {
    const btn = $('#copy-link');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(window.location.href);
            btn.dataset.state = 'done';
            const label = $('#copy-link-label');
            if (label) { const old = label.textContent; label.textContent = 'Ссылка скопирована'; setTimeout(() => { label.textContent = old; }, 2200); }
        } catch { window.prompt('Скопируйте ссылку:', window.location.href); }
    });
}

/* ── Наверх ───────────────────────────────────────────────── */
function initTop() {
    const btn = $('#to-top');
    if (!btn) return;
    const sync = () => btn.classList.toggle('hidden', window.scrollY < 700);
    sync();
    window.addEventListener('scroll', sync, { passive: true });
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

[initHeader, initMenu, initScrollSpy, initReveal, initClock, initA11y, initBoard, initExpanders, initAppeal, initShare, initTop]
    .forEach(fn => { try { fn(); } catch (e) { console.error('[ufa]', fn.name, e); } });
