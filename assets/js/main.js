/* ============================================================
   Scmlds – Main JavaScript
   Bootstrap 5.3 · Canvas animations · Live ticker · 60s modal
   ============================================================ */

(function () {
    'use strict';

    // ===== NAVBAR scroll effect =====
    const mainNav = document.getElementById('mainNav');
    if (mainNav) {
        window.addEventListener('scroll', () => {
            mainNav.classList.toggle('scrolled', window.scrollY > 50);
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
                const top = target.getBoundingClientRect().top + window.scrollY - 110;
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

    const counterEls = document.querySelectorAll('[data-counter]');
    if (counterEls.length) {
        const obs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    animateCounter(e.target, parseInt(e.target.dataset.counter, 10), 1800);
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.3 });
        counterEls.forEach(el => obs.observe(el));
    }

    // ===== Scroll reveal =====
    const aosEls = document.querySelectorAll('[data-aos]');
    if (aosEls.length) {
        const revObs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.style.opacity = '1';
                    e.target.style.transform = 'none';
                    revObs.unobserve(e.target);
                }
            });
        }, { threshold: 0.08 });
        aosEls.forEach(el => {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            revObs.observe(el);
        });
    }

    // ===== Scam Modal =====
    const scamModal = document.getElementById('scamModal');
    if (scamModal) {
        scamModal.addEventListener('show.bs.modal', e => {
            const t = e.relatedTarget;
            document.getElementById('scamModalTitle').textContent = t.dataset.scamType || '';
            document.getElementById('scamModalDesc').textContent  = t.dataset.scamDesc || '';
        });
    }

    // ===== Bootstrap form validation (main page form) =====
    const leadForm = document.getElementById('leadForm');
    if (leadForm) {
        leadForm.addEventListener('submit', function (e) {
            if (!leadForm.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
            leadForm.classList.add('was-validated');
        });
    }

    // ===== Modal form validation (fallPruefenModal) =====
    const modalLeadForm = document.getElementById('modalLeadForm');
    if (modalLeadForm) {
        modalLeadForm.addEventListener('submit', function (e) {
            if (!modalLeadForm.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
            modalLeadForm.classList.add('was-validated');
        });
    }

    // ===== Engagement form validation =====
    const engForm = document.getElementById('engagementForm');
    if (engForm) {
        engForm.addEventListener('submit', function (e) {
            if (!engForm.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
            engForm.classList.add('was-validated');
        });
    }

    // ===== Pre-fill modal from hero quick form =====
    const fallModal = document.getElementById('fallPruefenModal');
    if (fallModal) {
        fallModal.addEventListener('show.bs.modal', () => {
            const name  = (document.getElementById('heroName')?.value || '').trim();
            const email = (document.getElementById('heroEmail')?.value || '').trim();
            const amt   = (document.getElementById('heroAmount')?.value || '').trim();

            if (name) {
                const parts = name.split(' ');
                const fnEl  = document.getElementById('modalFirstName');
                const lnEl  = document.getElementById('modalLastName');
                if (fnEl) fnEl.value = parts[0] || '';
                if (lnEl) lnEl.value = parts.slice(1).join(' ') || '';
            }
            const emEl = document.getElementById('modalEmail');
            if (email && emEl) emEl.value = email;

            const amEl = document.getElementById('modalAmount');
            if (amt && amEl) amEl.value = amt;
        });
    }

    // ===== Mobile navbar close on link click =====
    const navCollapse = document.getElementById('navContent');
    if (navCollapse) {
        document.querySelectorAll('#navContent .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const bsc = bootstrap.Collapse.getInstance(navCollapse);
                if (bsc) bsc.hide();
            });
        });
    }

    // ============================================================
    //  LIVE CRYPTO TICKER STRIP
    // ============================================================
    const ASSETS = [
        { sym: 'BTC',     price: 67241.50, dec: 2,  icon: '₿' },
        { sym: 'ETH',     price: 3541.20,  dec: 2,  icon: 'Ξ' },
        { sym: 'XRP',     price: 0.5312,   dec: 4,  icon: '✕' },
        { sym: 'SOL',     price: 178.44,   dec: 2,  icon: '◎' },
        { sym: 'BNB',     price: 412.80,   dec: 2,  icon: '♦' },
        { sym: 'ADA',     price: 0.4821,   dec: 4,  icon: '♠' },
        { sym: 'EUR/USD', price: 1.0842,   dec: 4,  icon: '€' },
        { sym: 'GBP/USD', price: 1.2711,   dec: 4,  icon: '£' },
        { sym: 'USD/JPY', price: 153.24,   dec: 2,  icon: '¥' },
        { sym: 'GOLD',    price: 2318.50,  dec: 2,  icon: '⬛' },
        { sym: 'OIL',     price: 78.34,    dec: 2,  icon: '⬛' },
        { sym: 'DOGE',    price: 0.1623,   dec: 4,  icon: 'Ð' },
    ];

    const tickerTrack = document.getElementById('tickerTrack');
    if (tickerTrack) {
        // Build initial ticker HTML (duplicated for seamless loop)
        function buildTicker() {
            const items = ASSETS.map(a => {
                const chg  = (Math.random() * 6 - 2).toFixed(2);
                const isUp = parseFloat(chg) >= 0;
                const price = (a.price * (1 + parseFloat(chg) / 100)).toFixed(a.dec);
                return `<span class="ticker-item">
                    <span class="sym">${a.sym}</span>
                    <span class="val">${Number(price).toLocaleString('de-DE', { minimumFractionDigits: a.dec })}</span>
                    <span class="${isUp ? 'chg-up' : 'chg-down'}">${isUp ? '+' : ''}${chg}%</span>
                </span>`;
            }).join('');
            // Duplicate for seamless scroll
            tickerTrack.innerHTML = items + items;
        }
        buildTicker();

        // Update prices every 3 seconds
        setInterval(() => {
            document.querySelectorAll('#tickerTrack .ticker-item').forEach((item, idx) => {
                const asset = ASSETS[idx % ASSETS.length];
                const chg   = (Math.random() * 4 - 1.5).toFixed(2);
                const isUp  = parseFloat(chg) >= 0;
                const price = (asset.price * (1 + parseFloat(chg) / 100)).toFixed(asset.dec);
                const valEl = item.querySelector('.val');
                const chgEl = item.querySelector('.chg-up, .chg-down');
                if (valEl) valEl.textContent = Number(price).toLocaleString('de-DE', { minimumFractionDigits: asset.dec });
                if (chgEl) {
                    chgEl.className = isUp ? 'chg-up' : 'chg-down';
                    chgEl.textContent = (isUp ? '+' : '') + chg + '%';
                }
            });
        }, 3000);
    }

    // ============================================================
    //  FLOATING PRICE TAGS (Hero)
    // ============================================================
    const PRICE_TAGS = [
        { id: 'ptBTC',    base: 67241.50, dec: 2,  sym: 'BTC',     icon: '₿' },
        { id: 'ptETH',    base: 3541.20,  dec: 2,  sym: 'ETH',     icon: 'Ξ' },
        { id: 'ptEURUSD', base: 1.0842,   dec: 4,  sym: 'EUR/USD', icon: '€' },
        { id: 'ptXRP',    base: 0.5312,   dec: 4,  sym: 'XRP',     icon: '✕' },
    ];

    PRICE_TAGS.forEach(tag => {
        let currentPrice = tag.base;
        setInterval(() => {
            const el = document.getElementById(tag.id);
            if (!el) return;
            const drift = (Math.random() * 0.4 - 0.15) / 100;
            currentPrice *= (1 + drift);
            const chg = ((currentPrice - tag.base) / tag.base * 100).toFixed(2);
            const isUp = parseFloat(chg) >= 0;

            const valEl = el.querySelector('.pt-val');
            const chgEl = el.querySelector('.pt-chg');
            if (valEl) valEl.textContent = currentPrice.toLocaleString('de-DE', { minimumFractionDigits: tag.dec, maximumFractionDigits: tag.dec });
            if (chgEl) {
                chgEl.className = 'pt-chg ' + (isUp ? 'up' : 'down');
                chgEl.textContent = (isUp ? '+' : '') + chg + '%';
            }
        }, 2500);
    });

    // ============================================================
    //  HERO CANVAS – Animated financial chart lines
    // ============================================================
    const heroBg = document.getElementById('heroBg');
    if (heroBg) {
        const ctx = heroBg.getContext('2d');
        let w, h, lines;

        function resizeCanvas() {
            w = heroBg.width  = heroBg.offsetWidth;
            h = heroBg.height = heroBg.offsetHeight;
        }

        function makeChartLine(yBase, color, speed, amplitude, nPoints) {
            const pts = [];
            for (let i = 0; i <= nPoints; i++) pts.push({ x: (w / nPoints) * i, y: yBase + (Math.random() - 0.5) * amplitude });
            return { pts, color, speed, amplitude, yBase, phase: Math.random() * Math.PI * 2 };
        }

        function initLines() {
            lines = [
                makeChartLine(h * 0.30, 'rgba(245,166,35,0.7)',  0.3, h * 0.18, 50),
                makeChartLine(h * 0.55, 'rgba(34,197,94,0.55)',  0.2, h * 0.14, 45),
                makeChartLine(h * 0.70, 'rgba(99,179,237,0.45)', 0.15, h * 0.12, 40),
                makeChartLine(h * 0.40, 'rgba(255,255,255,0.18)',0.1, h * 0.08, 60),
            ];
        }

        function catmullRom(ctx, pts) {
            ctx.beginPath();
            ctx.moveTo(pts[0].x, pts[0].y);
            for (let i = 0; i < pts.length - 1; i++) {
                const p0 = pts[Math.max(0, i - 1)];
                const p1 = pts[i];
                const p2 = pts[i + 1];
                const p3 = pts[Math.min(pts.length - 1, i + 2)];
                const cp1x = p1.x + (p2.x - p0.x) / 6;
                const cp1y = p1.y + (p2.y - p0.y) / 6;
                const cp2x = p2.x - (p3.x - p1.x) / 6;
                const cp2y = p2.y - (p3.y - p1.y) / 6;
                ctx.bezierCurveTo(cp1x, cp1y, cp2x, cp2y, p2.x, p2.y);
            }
        }

        let t = 0;
        function drawHero() {
            ctx.clearRect(0, 0, w, h);
            t += 0.01;

            lines.forEach(line => {
                // Animate points
                line.pts.forEach((pt, i) => {
                    pt.y = line.yBase + Math.sin(t * line.speed + i * 0.5 + line.phase) * line.amplitude * 0.5
                                      + Math.sin(t * line.speed * 2.3 + i * 0.3) * line.amplitude * 0.3;
                });

                // Draw filled area
                ctx.save();
                catmullRom(ctx, line.pts);
                ctx.lineTo(w, h);
                ctx.lineTo(0, h);
                ctx.closePath();
                const grad = ctx.createLinearGradient(0, 0, 0, h);
                // Derive a low-opacity fill color from the line's rgba value
                const fillColor = line.color.replace(/[\d.]+\)$/, '0.10)');
                grad.addColorStop(0, fillColor);
                grad.addColorStop(1, 'transparent');
                ctx.fillStyle = grad;
                ctx.fill();
                ctx.restore();

                // Draw line
                catmullRom(ctx, line.pts);
                ctx.strokeStyle = line.color;
                ctx.lineWidth = 1.5;
                ctx.stroke();
            });

            requestAnimationFrame(drawHero);
        }

        resizeCanvas();
        initLines();
        drawHero();
        window.addEventListener('resize', () => { resizeCanvas(); initLines(); });
    }

    // ============================================================
    //  AI NETWORK CANVAS – 3D Neural network visualization
    // ============================================================
    const aiCanvas = document.getElementById('aiNetworkCanvas');
    if (aiCanvas) {
        const ctx2 = aiCanvas.getContext('2d');
        let w2, h2, nodes2, edges2, t2 = 0;
        let aiNodeCount = 0, aiEdgeCount = 0, aiScamCount = 0, aiRecoveredAmt = 0;

        function resizeAI() {
            w2 = aiCanvas.width  = aiCanvas.offsetWidth  || 600;
            h2 = aiCanvas.height = aiCanvas.offsetHeight || 450;
        }

        function initNetwork() {
            nodes2 = [];
            edges2 = [];
            const N = 22;
            for (let i = 0; i < N; i++) {
                const role = i < 3 ? 'fraud' : (i < 6 ? 'recovered' : 'neutral');
                nodes2.push({
                    x:    40 + Math.random() * (w2 - 80),
                    y:    40 + Math.random() * (h2 - 120),
                    r:    role === 'fraud' ? 10 : (role === 'recovered' ? 8 : 5 + Math.random() * 4),
                    role,
                    vx:   (Math.random() - 0.5) * 0.4,
                    vy:   (Math.random() - 0.5) * 0.4,
                    pulse: Math.random() * Math.PI * 2,
                    active: false,
                    activeTick: 0,
                });
            }
            // Build edges
            for (let i = 0; i < N; i++) {
                const conn = 2 + Math.floor(Math.random() * 3);
                for (let c = 0; c < conn; c++) {
                    let j = Math.floor(Math.random() * N);
                    if (j !== i) edges2.push({ a: i, b: j, flow: Math.random(), highlight: false });
                }
            }
        }

        function drawAI() {
            ctx2.clearRect(0, 0, w2, h2);
            t2 += 0.025;

            // Subtle grid
            ctx2.strokeStyle = 'rgba(255,255,255,0.03)';
            ctx2.lineWidth = 1;
            for (let x = 0; x < w2; x += 40) { ctx2.beginPath(); ctx2.moveTo(x, 0); ctx2.lineTo(x, h2); ctx2.stroke(); }
            for (let y = 0; y < h2 - 60; y += 40) { ctx2.beginPath(); ctx2.moveTo(0, y); ctx2.lineTo(w2, y); ctx2.stroke(); }

            // Move nodes
            nodes2.forEach(n => {
                n.x += n.vx; n.y += n.vy;
                if (n.x < n.r || n.x > w2 - n.r) n.vx *= -1;
                if (n.y < n.r || n.y > h2 - 80 - n.r) n.vy *= -1;
                n.pulse += 0.04;
                if (n.activeTick > 0) n.activeTick--;
            });

            // Random activation
            if (Math.random() < 0.04) {
                const idx = Math.floor(Math.random() * nodes2.length);
                nodes2[idx].active    = true;
                nodes2[idx].activeTick = 30;
            }

            // Draw edges
            edges2.forEach(e => {
                const a = nodes2[e.a], b = nodes2[e.b];
                const aColor = a.role === 'fraud' ? 'rgba(239,68,68,0.25)' : (a.role === 'recovered' ? 'rgba(34,197,94,0.2)' : 'rgba(99,179,237,0.12)');
                ctx2.beginPath();
                ctx2.moveTo(a.x, a.y);
                ctx2.lineTo(b.x, b.y);
                ctx2.strokeStyle = aColor;
                ctx2.lineWidth = 0.8;
                ctx2.stroke();

                // Animated flow dot along the edge
                const progress = (t2 * 0.5 + e.flow) % 1;
                const dx = b.x - a.x, dy = b.y - a.y;
                const fx = a.x + dx * progress, fy = a.y + dy * progress;
                ctx2.beginPath();
                ctx2.arc(fx, fy, 2, 0, Math.PI * 2);
                ctx2.fillStyle = a.role === 'fraud' ? 'rgba(239,68,68,0.8)' : (a.role === 'recovered' ? 'rgba(34,197,94,0.8)' : 'rgba(245,166,35,0.6)');
                ctx2.fill();
            });

            // Draw nodes
            nodes2.forEach(n => {
                const pulseR = n.r + Math.sin(n.pulse) * 2;
                const isActive = n.activeTick > 0;

                // Glow ring
                if (isActive || n.role === 'fraud' || n.role === 'recovered') {
                    const ringColor = n.role === 'fraud'      ? 'rgba(239,68,68,0.25)' :
                                      n.role === 'recovered'  ? 'rgba(34,197,94,0.25)' :
                                                                 'rgba(245,166,35,0.25)';
                    ctx2.beginPath();
                    ctx2.arc(n.x, n.y, pulseR + 6, 0, Math.PI * 2);
                    ctx2.fillStyle = ringColor;
                    ctx2.fill();
                }

                // Main node
                const grad = ctx2.createRadialGradient(n.x - n.r * 0.3, n.y - n.r * 0.3, 0, n.x, n.y, pulseR);
                if (n.role === 'fraud') {
                    grad.addColorStop(0, '#f87171');
                    grad.addColorStop(1, '#dc2626');
                } else if (n.role === 'recovered') {
                    grad.addColorStop(0, '#4ade80');
                    grad.addColorStop(1, '#16a34a');
                } else if (isActive) {
                    grad.addColorStop(0, '#fde68a');
                    grad.addColorStop(1, '#f59e0b');
                } else {
                    grad.addColorStop(0, '#93c5fd');
                    grad.addColorStop(1, '#1d4ed8');
                }
                ctx2.beginPath();
                ctx2.arc(n.x, n.y, pulseR, 0, Math.PI * 2);
                ctx2.fillStyle = grad;
                ctx2.fill();
            });

            // Animate HUD counters
            const eln = document.getElementById('aiNodes');
            const ele = document.getElementById('aiEdges');
            const els = document.getElementById('aiScams');
            const elr = document.getElementById('aiRecovered');
            if (Math.random() < 0.05) {
                aiNodeCount = Math.min(aiNodeCount + Math.floor(Math.random() * 4) + 1, 8472);
                aiEdgeCount = Math.min(aiEdgeCount + Math.floor(Math.random() * 8) + 2, 24189);
                aiScamCount = Math.min(aiScamCount + (Math.random() < 0.2 ? 1 : 0), 312);
                aiRecoveredAmt = Math.min(aiRecoveredAmt + Math.floor(Math.random() * 3000), 4820000);
                if (eln) eln.textContent = aiNodeCount.toLocaleString('de-DE');
                if (ele) ele.textContent = aiEdgeCount.toLocaleString('de-DE');
                if (els) els.textContent = aiScamCount.toLocaleString('de-DE');
                if (elr) elr.textContent = '€' + aiRecoveredAmt.toLocaleString('de-DE');
            }

            requestAnimationFrame(drawAI);
        }

        resizeAI();
        initNetwork();
        drawAI();
        window.addEventListener('resize', () => { resizeAI(); initNetwork(); });

        // Cycle AI step highlights
        let stepIdx = 1;
        setInterval(() => {
            for (let i = 1; i <= 4; i++) {
                const el = document.getElementById('aiStep' + i);
                if (el) el.classList.toggle('active', i === stepIdx);
            }
            stepIdx = stepIdx >= 4 ? 1 : stepIdx + 1;
        }, 2500);
    }

    // ============================================================
    //  60-SECOND ENGAGEMENT MODAL
    // ============================================================
    const engModal = document.getElementById('exitIntentModal');
    if (engModal) {
        let shown = false;

        function isAlreadyEngaged() {
            return sessionStorage.getItem('scmlds_engaged') === '1';
        }

        function markEngaged() {
            sessionStorage.setItem('scmlds_engaged', '1');
        }

        function showEngModal() {
            if (shown || isAlreadyEngaged()) return;
            shown = true;
            const bsModal = new bootstrap.Modal(engModal);
            bsModal.show();
        }

        // Mark as engaged when any form is submitted
        document.querySelectorAll('form').forEach(f => {
            f.addEventListener('submit', () => markEngaged());
        });

        // Mark as engaged when fallPruefenModal is opened
        const fpModal = document.getElementById('fallPruefenModal');
        if (fpModal) fpModal.addEventListener('show.bs.modal', () => markEngaged());

        // Trigger after 60 seconds
        setTimeout(showEngModal, 60000);

        // Also trigger on mouse leaving the viewport (exit intent, desktop only)
        document.addEventListener('mouseleave', e => {
            if (e.clientY < 5 && !shown && !isAlreadyEngaged()) showEngModal();
        });
    }

})();
