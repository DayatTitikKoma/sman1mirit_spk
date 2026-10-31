// ============================================================
// assets/js/main.js
// JavaScript utama untuk SPK MOORA
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ---- Aktifkan nav-item berdasarkan URL ----
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href) && href !== '/') {
            link.classList.add('active');
        }
    });

    // ---- Step toggle (accordion untuk halaman detail) ----
    document.querySelectorAll('.step-header').forEach(header => {
        header.addEventListener('click', function () {
            const body = this.nextElementSibling;
            const toggle = this.querySelector('.step-toggle');
            if (body && body.classList.contains('step-body')) {
                body.classList.toggle('open');
                if (toggle) {
                    toggle.textContent = body.classList.contains('open') ? '▲' : '▼';
                }
            }
        });
    });

    // Buka step pertama secara default
    const firstStepBody = document.querySelector('.step-body');
    if (firstStepBody) {
        firstStepBody.classList.add('open');
        const toggle = firstStepBody.previousElementSibling?.querySelector('.step-toggle');
        if (toggle) toggle.textContent = '▲';
    }

    // ---- Animasi confidence bar ----
    document.querySelectorAll('.confidence-fill').forEach(bar => {
        const target = bar.getAttribute('data-value') || '0';
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = parseFloat(target).toFixed(1) + '%';
        }, 100);
    });

    // ---- Upload zone drag and drop ----
    const uploadZone = document.querySelector('.upload-zone');
    const fileInput  = document.querySelector('.upload-zone input[type="file"]');

    if (uploadZone && fileInput) {
        uploadZone.addEventListener('click', () => fileInput.click());

        uploadZone.addEventListener('dragover', e => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', e => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            const dt = e.dataTransfer;
            if (dt.files.length) {
                fileInput.files = dt.files;
                updateUploadZoneLabel(dt.files[0].name);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                updateUploadZoneLabel(fileInput.files[0].name);
            }
        });

        function updateUploadZoneLabel(filename) {
            const textEl = uploadZone.querySelector('.upload-text');
            if (textEl) {
                textEl.textContent = '✅ ' + filename;
            }
        }
    }

    // ---- Toast Notifications ----
    window.showToast = function (message, type = 'info', duration = 3500) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = { success: '✅', danger: '❌', info: 'ℹ️', warning: '⚠️' };
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<span>${icons[type] || 'ℹ️'}</span><span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // ---- Modal ----
    window.openModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function (id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }
    };

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) closeModal(modal.id);
        });
    });

    // ---- Sortable Table ----
    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.title = 'Klik untuk urutkan';
        th.addEventListener('click', function () {
            const table  = this.closest('table');
            const tbody  = table.querySelector('tbody');
            const colIdx = Array.from(this.parentNode.children).indexOf(this);
            const asc    = this.dataset.sortDir !== 'asc';

            // Reset semua header
            table.querySelectorAll('th[data-sort]').forEach(h => {
                h.dataset.sortDir = '';
                h.querySelector('.sort-icon')?.remove();
            });

            this.dataset.sortDir = asc ? 'asc' : 'desc';
            const icon = document.createElement('span');
            icon.className = 'sort-icon';
            icon.textContent = asc ? ' ↑' : ' ↓';
            icon.style.color = 'var(--ipa-light)';
            this.appendChild(icon);

            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const aText = a.cells[colIdx]?.dataset.raw ?? a.cells[colIdx]?.textContent.trim() ?? '';
                const bText = b.cells[colIdx]?.dataset.raw ?? b.cells[colIdx]?.textContent.trim() ?? '';
                const aVal  = isNaN(aText) ? aText : parseFloat(aText);
                const bVal  = isNaN(bText) ? bText : parseFloat(bText);
                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    return asc ? aVal - bVal : bVal - aVal;
                }
                return asc ? String(aVal).localeCompare(String(bVal)) : String(bVal).localeCompare(String(aVal));
            });

            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // ---- Filter / Search Tabel ----
    const searchInput = document.getElementById('search-input');
    const searchTarget = document.getElementById('search-table');

    if (searchInput && searchTarget) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            searchTarget.querySelectorAll('tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // ---- Filter Rekomendasi ----
    const filterSelect = document.getElementById('filter-rekomendasi');
    if (filterSelect && searchTarget) {
        filterSelect.addEventListener('change', function () {
            const val = this.value;
            searchTarget.querySelectorAll('tbody tr').forEach(tr => {
                const rekTd = tr.querySelector('[data-rekomendasi]');
                if (!val || !rekTd) {
                    tr.style.display = '';
                } else {
                    tr.style.display = rekTd.dataset.rekomendasi === val ? '' : 'none';
                }
            });
        });
    }

    // ---- Confirm Delete ----
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // ---- Auto-dismiss alerts ----
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        const delay = parseInt(alert.dataset.autoDismiss) || 4000;
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.4s ease';
            setTimeout(() => alert.remove(), 400);
        }, delay);
    });

    // ---- Chart.js: Distribusi Rekomendasi ----
    const chartEl = document.getElementById('chart-distribusi');
    if (chartEl && window.Chart) {
        const ipa   = parseInt(chartEl.dataset.ipa)   || 0;
        const ips   = parseInt(chartEl.dataset.ips)   || 0;
        const total = ipa + ips;

        new Chart(chartEl, {
            type: 'doughnut',
            data: {
                labels: [`IPA (${ipa})`, `IPS (${ips})`],
                datasets: [{
                    data: [ipa, ips],
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(16, 185, 129, 0.8)'
                    ],
                    borderColor: [
                        'rgba(99, 102, 241, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#8892b0',
                            padding: 16,
                            font: { size: 12, family: 'Inter' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return `${ctx.label}: ${pct}%`;
                            }
                        }
                    }
                }
            }
        });
    }

    // ---- Chart Confidence Bar ----
    const chartConf = document.getElementById('chart-confidence');
    if (chartConf && window.Chart) {
        const labels = JSON.parse(chartConf.dataset.labels || '[]');
        const data   = JSON.parse(chartConf.dataset.values || '[]');

        new Chart(chartConf, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Confidence (%)',
                    data,
                    backgroundColor: data.map(v => v >= 50 ? 'rgba(99,102,241,0.7)' : 'rgba(16,185,129,0.7)'),
                    borderColor:     data.map(v => v >= 50 ? 'rgba(99,102,241,1)' : 'rgba(16,185,129,1)'),
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#8892b0', callback: v => v + '%' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#8892b0', maxRotation: 45, font: { size: 11 } }
                    }
                }
            }
        });
    }

    // ---- Print / PDF ----
    document.querySelectorAll('[data-print]').forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });

    // ---- Fade-in animasi rows ----
    document.querySelectorAll('tbody tr').forEach((tr, i) => {
        tr.style.animationDelay = `${i * 30}ms`;
        tr.classList.add('animate-fade-in');
    });
});
