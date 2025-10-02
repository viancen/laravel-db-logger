// logs.js
(() => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const qs = (sel) => document.querySelector(sel);
        const qsa = (sel) => Array.from(document.querySelectorAll(sel));

        if (!window.logsConfig) {
            console.error('logsConfig niet gevonden');
            return;
        }

        const API = window.logsConfig.apiUrl;
        const DEFAULTS = window.logsConfig.defaults;

        let state = {
            page: 1,
            lastPage: 1,
            loading: false,
            auto: false,
            timer: null,
        };

        const elRows = qs('#rows');
        const elMeta = qs('#meta');
        const elPageInfo = qs('#pageinfo');
        const elModal = qs('#log-modal');
        const elModalBody = qs('#modal-body');
        const elModalClose = qs('#modal-close');

        if (!elRows || !elMeta || !elPageInfo || !elModal || !elModalBody) {
            console.error('Niet alle vereiste elementen gevonden');
            return;
        }

        initMultiselect();

        const btnApply = qs('#btn-apply');
        const btnReset = qs('#btn-reset');
        const btnPrev = qs('#prev');
        const btnNext = qs('#next');
        const btnExport = qs('#btn-export');
        const fAuto = qs('#f-auto');

        if (btnApply) btnApply.addEventListener('click', () => {
            state.page = 1;
            fetchData();
        });

        if (btnReset) btnReset.addEventListener('click', () => {
            resetFilters();
            state.page = 1;
            fetchData();
        });

        if (btnPrev) btnPrev.addEventListener('click', () => {
            if (state.page > 1) {
                state.page--;
                fetchData();
            }
        });

        if (btnNext) btnNext.addEventListener('click', () => {
            if (state.page < state.lastPage) {
                state.page++;
                fetchData();
            }
        });

        if (btnExport) btnExport.addEventListener('click', exportJSON);

        if (fAuto) fAuto.addEventListener('change', (e) => {
            state.auto = e.target.checked;
            if (state.auto) {
                state.timer = setInterval(() => fetchData(false), 10000);
            } else if (state.timer) {
                clearInterval(state.timer);
            }
        });

        if (elModalClose) elModalClose.addEventListener('click', closeModal);

        if (elModal) {
            qs('.modal-overlay')?.addEventListener('click', closeModal);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && elModal.classList.contains('open')) {
                    closeModal();
                }
            });
        }

        qsa('input, select').forEach(el => {
            if (el.type !== 'checkbox') {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        state.page = 1;
                        fetchData();
                    }
                });
            }
        });

        function initMultiselect() {
            const multiselect = qs('#levels-select');
            if (!multiselect) return;

            const trigger = multiselect.querySelector('.multiselect-trigger');
            const valueDisplay = multiselect.querySelector('.multiselect-value');
            const checkboxes = multiselect.querySelectorAll('input[type="checkbox"]');

            function updateDisplay() {
                const checked = Array.from(checkboxes).filter(cb => cb.checked);
                if (checked.length === 0) {
                    valueDisplay.textContent = 'Selecteer levels...';
                    trigger.classList.remove('has-value');
                } else if (checked.length === checkboxes.length) {
                    valueDisplay.textContent = 'Alle levels';
                    trigger.classList.add('has-value');
                } else {
                    const labels = checked.map(cb => {
                        const label = cb.parentElement.querySelector('span').textContent;
                        return label.split(' — ')[1];
                    });
                    valueDisplay.textContent = labels.join(', ');
                    trigger.classList.add('has-value');
                }
            }

            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                multiselect.classList.toggle('open');
            });

            document.addEventListener('click', (e) => {
                if (!multiselect.contains(e.target)) {
                    multiselect.classList.remove('open');
                }
            });

            checkboxes.forEach(cb => cb.addEventListener('change', updateDisplay));
            updateDisplay();
        }

        function readFilters() {
            const levelsCheckboxes = qsa('#levels-select input[type="checkbox"]:checked');
            const levels = levelsCheckboxes.map(cb => cb.value);

            return {
                from: qs('#f-from')?.value || '',
                to: qs('#f-to')?.value || '',
                levels,
                channel: qs('#f-channel')?.value.trim() || '',
                q: qs('#f-q')?.value.trim() || '',
                user_id: qs('#f-user')?.value.trim() || '',
                request_id: qs('#f-rid')?.value.trim() || '',
                ip: qs('#f-ip')?.value.trim() || '',
                per_page: qs('#f-per')?.value || '50',
                sort: qs('#f-sort')?.value || 'created_at',
                dir: qs('#f-dir')?.value || 'desc'
            };
        }

        function buildQuery(obj) {
            const p = new URLSearchParams();
            Object.entries(obj).forEach(([k, v]) => {
                if (Array.isArray(v)) {
                    v.forEach(x => x !== '' && p.append(k + '[]', x));
                } else if (v !== null && v !== undefined && v !== '') {
                    p.append(k, v);
                }
            });
            p.append('page', state.page);
            return p.toString();
        }

        function resetFilters() {
            ['#f-channel', '#f-q', '#f-user', '#f-rid', '#f-ip'].forEach(id => {
                const el = qs(id);
                if (el) el.value = '';
            });

            const fFrom = qs('#f-from');
            const fTo = qs('#f-to');
            const fPer = qs('#f-per');
            const fSort = qs('#f-sort');
            const fDir = qs('#f-dir');

            if (fFrom) fFrom.value = DEFAULTS.from || '';
            if (fTo) fTo.value = '';
            if (fPer) fPer.value = DEFAULTS.per_page || '50';
            if (fSort) fSort.value = DEFAULTS.sort || 'created_at';
            if (fDir) fDir.value = DEFAULTS.dir || 'desc';

            qsa('#levels-select input[type="checkbox"]').forEach(cb => {
                cb.checked = DEFAULTS.levels.includes(cb.value);
            });

            const multiselect = qs('#levels-select');
            if (multiselect) {
                const trigger = multiselect.querySelector('.multiselect-trigger');
                const valueDisplay = multiselect.querySelector('.multiselect-value');
                const checkboxes = qsa('#levels-select input[type="checkbox"]');
                const checked = Array.from(checkboxes).filter(cb => cb.checked);

                if (checked.length === checkboxes.length) {
                    valueDisplay.textContent = 'Alle levels';
                    trigger.classList.add('has-value');
                }
            }

            qsa('select.form-control').forEach(sel => {
                sel.classList.toggle('has-value', sel.value !== '');
            });
        }

        function fmtTime(iso) {
            if (!iso) return '';
            try {
                return new Date(iso).toLocaleString('nl-NL', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            } catch {
                return iso;
            }
        }

        function truncate(str, len) {
            return str && str.length > len ? str.substring(0, len) + '…' : str || '';
        }

        function renderRows(items) {
            if (!elRows) return;

            elRows.innerHTML = '';
            if (!items || items.length === 0) {
                elRows.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:2rem; color:var(--muted)">Geen logs gevonden</td></tr>';
                return;
            }

            items.forEach(row => {
                const tr = document.createElement('tr');
                const hasException = row.has_exception ? '🔥 ' : '';
                tr.innerHTML = `
                    <td class="mono">${row.id}</td>
                    <td class="mono small">${fmtTime(row.created_at)}</td>
                    <td><span class="level lv-${row.level_label}">${row.level} ${row.level_label}</span></td>
                    <td><span class="badge">${row.channel ?? ''}</span></td>
                    <td><div class="msg">${hasException}${escapeHtml(row.message ?? '')}</div></td>
                    <td class="mono small">${truncate(row.user_id, 8)}</td>
                    <td class="mono small">${truncate(row.request_id, 8)}</td>
                    <td class="mono small">${row.ip_address ?? ''}</td>
                `.trim();
                tr.addEventListener('dblclick', () => openModal(row));
                elRows.appendChild(tr);
            });
        }

        function openModal(row) {
            if (!elModal || !elModalBody) return;

            let exceptionHtml = '';
            if (row.has_exception) {
                exceptionHtml = `
                    <div class="detail-section" style="grid-column: 1 / -1;">
                        <div class="detail-label">Exception / Stack Trace</div>
                        ${row.exception_class ? `<div class="exception-class">${escapeHtml(row.exception_class)}</div>` : ''}
                        ${row.exception_message ? `<div class="exception-message">${escapeHtml(row.exception_message)}</div>` : ''}
                        ${row.exception_trace ? `<pre class="stacktrace">${formatStackTrace(row.exception_trace)}</pre>` : ''}
                    </div>
                `;
            }

            elModalBody.innerHTML = `
                <div class="detail-grid">
                    ${exceptionHtml}
                    <div class="detail-section">
                        <div class="detail-label">Message</div>
                        <pre>${escapeHtml(row.message ?? '')}</pre>
                    </div>
                    <div class="detail-section">
                        <div class="detail-label">Meta</div>
                        <pre>${escapeHtml(JSON.stringify({
                id: row.id,
                created_at: row.created_at,
                level: `${row.level} — ${row.level_label}`,
                channel: row.channel,
                user_id: row.user_id,
                request_id: row.request_id,
                ip_address: row.ip_address,
                user_agent: row.user_agent
            }, null, 2))}</pre>
                    </div>
                    <div class="detail-section">
                        <div class="detail-label">Context</div>
                        <pre>${escapeHtml(JSON.stringify(row.context ?? {}, null, 2))}</pre>
                    </div>
                    <div class="detail-section">
                        <div class="detail-label">Extra</div>
                        <pre>${escapeHtml(JSON.stringify(row.extra ?? {}, null, 2))}</pre>
                    </div>
                </div>
            `;

            elModal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            if (!elModal) return;
            elModal.classList.remove('open');
            document.body.style.overflow = '';
        }

        function formatStackTrace(trace) {
            if (!trace) return '';

            if (Array.isArray(trace)) {
                return escapeHtml(trace.map((frame, i) => {
                    const file = frame.file || '?';
                    const line = frame.line || '?';
                    const fn = frame.function || frame.class || '?';
                    return `#${i} ${file}:${line}\n    ${fn}`;
                }).join('\n\n'));
            }

            const str = String(trace);
            return escapeHtml(str)
                .replace(/^(#\d+)/gm, '<span class="trace-num">$1</span>')
                .replace(/([\/\w\-\.]+\.php)(\(\d+\))?:/g, '<span class="trace-file">$1</span>$2:')
                .replace(/:(\d+)$/gm, ':<span class="trace-line">$1</span>');
        }

        function setMeta(total, page, last) {
            if (elMeta) elMeta.textContent = `${total} logs • Pagina ${page}/${last}`;
            if (elPageInfo) elPageInfo.textContent = `Pagina ${page} van ${last}`;
            if (btnPrev) btnPrev.disabled = page <= 1;
            if (btnNext) btnNext.disabled = page >= last;
        }

        function exportJSON() {
            const filters = readFilters();
            const url = API + '?' + buildQuery(filters);
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = `logs-export-p${data.page}-${Date.now()}.json`;
                    a.click();
                    URL.revokeObjectURL(a.href);
                })
                .catch(e => alert('Export mislukt: ' + e.message));
        }

        function escapeHtml(str) {
            return ('' + str).replace(/[&<>"']/g, (m) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[m]));
        }

        async function fetchData(scrollTop = true) {
            if (state.loading) return;
            state.loading = true;
            const filters = readFilters();
            const url = API + '?' + buildQuery(filters);

            try {
                const res = await fetch(url, {headers: {'Accept': 'application/json'}});
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();
                renderRows(data.data || []);
                state.lastPage = data.last_page || 1;
                setMeta(data.total || 0, data.page || 1, state.lastPage);

                if (scrollTop) window.scrollTo({top: 0, behavior: 'smooth'});
            } catch (e) {
                console.error(e);
                alert('Kon logs niet laden: ' + e.message);
            } finally {
                state.loading = false;
            }
        }

        fetchData();
    }
})();