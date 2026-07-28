/**
 * ============================================================
 *  Fitwell Milling Systems – Document Generator
 *  Vanilla JavaScript – All logic, state, rendering, storage
 * ============================================================
 */

// ===== STATE =====
const state = {
    activeDoc: 'dn',
    dn: { rows: [] },
    pf: { rows: [] },
};
function getDefaultRows() {
    return [{ qty: '', desc: '', rate: '', amt: '' }];
}
state.dn.rows = getDefaultRows();
state.pf.rows = getDefaultRows();

// ===== DOM REFS =====
const $ = id => document.getElementById(id);
const paperEl = $('paper');

// ===== UTILITY FUNCTIONS =====
function num(v) {
    const n = parseFloat((v || '').toString().replace(/,/g, ''));
    return isNaN(n) ? 0 : n;
}
function fmt(n) {
    return n ? n.toLocaleString('en-US') : '';
}
function esc(s) {
    return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;');
}
function val(id) { return $(id) ? $(id).value : ''; }
function setVal(id, v) { if ($(id)) $(id).value = v; }

// ===== NUMBER TO WORDS CONVERTER =====
function numberToWords(n) {
    if (n === 0) return 'Zero';
    const numStr = n.toFixed(2);
    const parts = numStr.split('.');
    const wholePart = parseInt(parts[0]);
    const decimalPart = parseInt(parts[1]);

    const units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
    const teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    const scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

    function convertChunk(num) {
        let words = '';
        let hundreds = Math.floor(num / 100);
        let rest = num % 100;
        if (hundreds > 0) {
            words += units[hundreds] + ' Hundred ';
        }
        if (rest > 0) {
            if (rest < 10) {
                words += units[rest] + ' ';
            } else if (rest < 20) {
                words += teens[rest - 10] + ' ';
            } else {
                let ten = Math.floor(rest / 10);
                let unit = rest % 10;
                words += tens[ten] + ' ';
                if (unit > 0) {
                    words += units[unit] + ' ';
                }
            }
        }
        return words;
    }

    let words = '';
    let num = wholePart;
    let scaleIndex = 0;
    while (num > 0) {
        let chunk = num % 1000;
        if (chunk !== 0) {
            let chunkWords = convertChunk(chunk);
            words = chunkWords + scales[scaleIndex] + ' ' + words;
        }
        num = Math.floor(num / 1000);
        scaleIndex++;
    }
    if (decimalPart > 0) {
        words += 'and ' + decimalPart + '/100';
    }
    return words.trim();
}

// ===== RENDER ITEMS ROWS (sidebar) =====
function renderRows(docKey) {
    const container = $(docKey + '-rows');
    if (!container) return;
    const rows = state[docKey].rows;
    container.innerHTML = '';
    rows.forEach((r, idx) => {
        const div = document.createElement('div');
        div.className = 'row-item';
        div.innerHTML = `
            <div class="row-desc">
                <p class="mini-label">Particulars</p>
                <input placeholder="Item / description" value="${esc(r.desc)}" data-idx="${idx}" data-field="desc" data-doc="${docKey}" />
            </div>
            <div class="row-grid">
                <div>
                    <p class="mini-label">Qty</p>
                    <input placeholder="Qty" value="${esc(r.qty)}" data-idx="${idx}" data-field="qty" data-doc="${docKey}" />
                </div>
                <div>
                    <p class="mini-label">Rate</p>
                    <input placeholder="Rate" value="${esc(r.rate)}" data-idx="${idx}" data-field="rate" data-doc="${docKey}" />
                </div>
                <div>
                    <p class="mini-label">Amount</p>
                    <input placeholder="Amount" value="${esc(r.amt)}" data-idx="${idx}" data-field="amt" data-doc="${docKey}" />
                </div>
            </div>
            <button class="remove-row" data-doc="${docKey}" data-idx="${idx}">Remove</button>
        `;
        container.appendChild(div);
    });
    container.querySelectorAll('input').forEach(inp => {
        inp.addEventListener('input', onRowInput);
    });
    container.querySelectorAll('.remove-row').forEach(btn => {
        btn.addEventListener('click', onRemoveRow);
    });
}

// ===== ROW EVENT HANDLERS =====
function onRowInput(e) {
    const inp = e.target;
    const doc = inp.dataset.doc;
    const idx = parseInt(inp.dataset.idx);
    const field = inp.dataset.field;
    if (state[doc] && state[doc].rows[idx] !== undefined) {
        state[doc].rows[idx][field] = inp.value;
        if (field === 'qty' || field === 'rate') {
            const q = num(state[doc].rows[idx].qty);
            const rate = num(state[doc].rows[idx].rate);
            const amt = (q && rate) ? q * rate : 0;
            state[doc].rows[idx].amt = amt ? fmt(amt) : '';
            const rowDiv = inp.closest('.row-item');
            const amtInput = rowDiv.querySelector('input[data-field="amt"]');
            if (amtInput) amtInput.value = state[doc].rows[idx].amt;
        }
        renderPreview();
        saveToStorage();
    }
}
function onRemoveRow(e) {
    const btn = e.target;
    const doc = btn.dataset.doc;
    const idx = parseInt(btn.dataset.idx);
    if (state[doc] && state[doc].rows.length > 1) {
        state[doc].rows.splice(idx, 1);
        renderRows(doc);
        renderPreview();
        saveToStorage();
    } else {
        alert('You need at least one item row.');
    }
}
function addRow(docKey) {
    state[docKey].rows.push({ qty: '', desc: '', rate: '', amt: '' });
    renderRows(docKey);
    renderPreview();
    saveToStorage();
}

// ===== PREVIEW RENDER =====
function renderPreview() {
    const addr = esc(val('hd-addr'));
    const pobox = esc(val('hd-pobox'));
    const phone = esc(val('hd-phone'));
    const email = esc(val('hd-email'));
    const company = esc(val('hd-name') || 'FITWELL MILLING SYSTEMS (U) LIMITED');
    const logo = val('hd-logo');
    const tin = esc(val('hd-tin'));
    const reg = esc(val('hd-reg'));
    const web = esc(val('hd-web'));

    let header = `
        <p class="co-name">${company}</p>
        ${logo ? `<img src="${logo}" style="display:block;margin:0 auto 8px;max-height:60px;" />` : ''}
        <p class="co-tag"><b>MILLING</b> SYSTEMS, SUPPLIES &amp; SERVICE</p>
        <p class="co-sub">SPARES, EQUIPMENT &amp; ACCESSORIES</p>
        <hr class="hr">
        <div class="addr-block">
            <p>${addr}</p>
            <p>P.O. Box: ${pobox}</p>
            <p>Tel: ${phone}</p>
            <p>Email: ${email}</p>
            ${tin ? `<p>TIN: ${tin}</p>` : ''}
            ${reg ? `<p>Reg. No.: ${reg}</p>` : ''}
            ${web ? `<p>Web: ${web}</p>` : ''}
        </div>
    `;

    let body = '';
    const active = state.activeDoc;

    // ----- Delivery Note -----
    if (active === 'dn') {
        const applyVat = $('dn-vat').checked;
        const rows = state.dn.rows.map(r => {
            const q = num(r.qty), rate = num(r.rate);
            const amt = r.amt ? num(r.amt) : (q && rate ? q * rate : 0);
            return { ...r, amt };
        });
        const subtotal = rows.reduce((s, r) => s + (r.amt || 0), 0);
        const vat = applyVat ? subtotal * 0.18 : 0;
        const total = subtotal + vat;

        const wordsInput = $('dn-words');
        if (wordsInput) wordsInput.value = numberToWords(total);

        const padded = rows.slice();
        while (padded.length < 8) padded.push({ qty: '', desc: '', rate: '', amt: 0 });
        let rowsHtml = padded.map(r => `
            <tr>
                <td class="num">${esc(r.qty) || '&nbsp;'}</td>
                <td>${esc(r.desc) || '&nbsp;'}</td>
                <td class="right">${r.rate ? esc(r.rate) : '&nbsp;'}</td>
                <td class="right">${r.amt ? fmt(r.amt) : '&nbsp;'}</td>
            </tr>
        `).join('');
        body = `
            <div class="title-box"><span>DELIVERY NOTE</span></div>
            <div class="meta-row">
                <span>No. ${esc(val('dn-no'))}</span>
                <span>Date: ${esc(val('dn-date')) || '____/____/____'}</span>
            </div>
            <p class="client-row"><b>Client name:</b> ${esc(val('dn-client')) || '&nbsp;'}</p>
            <p class="client-row" style="font-size:12px;"><b>Address:</b> ${esc(val('dn-client-addr')) || '&nbsp;'}</p>
            <div style="display:flex;gap:20px;font-size:12px;margin-bottom:12px;">
                <span><b>Contact:</b> ${esc(val('dn-contact')) || '&nbsp;'}</span>
                <span><b>Phone:</b> ${esc(val('dn-phone')) || '&nbsp;'}</span>
            </div>
            <table class="doc-table">
                <tr><th style="width:12%">Qty</th><th style="width:48%">Particulars</th><th style="width:20%">Rate (UGX/USD)</th><th style="width:20%">Amount (UGX/USD)</th></tr>
                ${rowsHtml}
                <tr><td>E.&amp;O.E</td><td></td><td style="font-weight:700;text-align:right;">Sub total</td><td class="right">${fmt(subtotal)}</td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">18% VAT</td><td class="right">${fmt(vat)}</td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">Total</td><td class="right">${fmt(total)}</td></tr>
            </table>
            <p class="fill-line"><b>Amount in words:</b><span class="under">${esc(val('dn-words'))}</span></p>
            <div class="sig-grid">
                <div><div class="sig-line">Delivered by: ${esc(val('dn-delby'))}</div></div>
                <div><div class="sig-line">Received by: ${esc(val('dn-recby'))}</div></div>
            </div>
        `;
    }
    // ----- Receipt -----
    else if (active === 'rc') {
        const amount = num(val('rc-amt'));
        const wordsInput = $('rc-words');
        if (wordsInput) wordsInput.value = numberToWords(amount);
        body = `
            <div class="title-box"><span>RECEIPT</span></div>
            <div class="meta-row">
                <span>No. ${esc(val('rc-no'))}</span>
                <span>Date: ${esc(val('rc-date')) || '____/____/____'}</span>
            </div>
            <p class="fill-line"><b>Received with thanks from:</b><span class="under">${esc(val('rc-from'))}</span></p>
            <p class="fill-line"><b>Being payment of:</b><span class="under">${esc(val('rc-for'))}</span></p>
            <p class="fill-line"><b>Payment Method:</b><span class="under">${esc(val('rc-method'))}</span></p>
            <p class="fill-line"><b>Amount in words:</b><span class="under">${esc(val('rc-words'))}</span></p>
            <p class="fill-line">
                <b>Cash / Cheque No.:</b><span class="under">${esc(val('rc-cash'))}</span>
                <b style="margin-left:20px;">Balance:</b><span class="under">${esc(val('rc-bal'))}</span>
            </p>
            <p class="fill-line" style="font-size:16px;font-weight:700;">
                <b>Amount (UGX/USD):</b><span class="under">${esc(val('rc-amt'))}</span>
            </p>
            <div class="sig-grid">
                <div><div class="sig-line">Issued by: ${esc(val('rc-issued'))}</div></div>
                <div><div class="sig-line">Signature</div></div>
            </div>
        `;
    }
    // ----- Proforma Invoice -----
    else if (active === 'pf') {
        const applyVat = $('pf-vat').checked;
        const rows = state.pf.rows.map(r => {
            const q = num(r.qty), rate = num(r.rate);
            const amt = r.amt ? num(r.amt) : (q && rate ? q * rate : 0);
            return { ...r, amt };
        });
        const subtotal = rows.reduce((s, r) => s + (r.amt || 0), 0);
        const vat = applyVat ? subtotal * 0.18 : 0;
        const total = subtotal + vat;

        const wordsInput = $('pf-words');
        if (wordsInput) wordsInput.value = numberToWords(total);

        const padded = rows.slice();
        while (padded.length < 6) padded.push({ qty: '', desc: '', rate: '', amt: 0 });
        let rowsHtml = padded.map(r => `
            <tr>
                <td class="num">${esc(r.qty) || '&nbsp;'}</td>
                <td>${esc(r.desc) || '&nbsp;'}</td>
                <td class="right">${r.rate ? esc(r.rate) : '&nbsp;'}</td>
                <td class="right">${r.amt ? fmt(r.amt) : '&nbsp;'}</td>
            </tr>
        `).join('');
        body = `
            <div class="title-box"><span>PROFORMA INVOICE</span></div>
            <div class="meta-row">
                <span>No. ${esc(val('pf-no'))}</span>
                <span>Date: ${esc(val('pf-date')) || '____/____/____'}</span>
            </div>
            <p class="client-row"><b>M/s:</b> ${esc(val('pf-client')) || '&nbsp;'}</p>
            <p class="client-row" style="font-size:12px;"><b>Address:</b> ${esc(val('pf-client-addr')) || '&nbsp;'}</p>
            <table class="doc-table">
                <tr><th style="width:14%">Qty</th><th style="width:46%">Particulars</th><th style="width:20%">Rate</th><th style="width:20%">Amount</th></tr>
                ${rowsHtml}
                <tr><td></td><td style="font-size:10.5px;">Terms: ${esc(val('pf-terms'))}. Contact: ${esc(val('pf-contact'))}</td><td style="font-weight:700;text-align:right;">Sub total</td><td class="right">${fmt(subtotal)}</td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">18% VAT</td><td class="right">${fmt(vat)}</td></tr>
                <tr><td>E.&amp;O.E</td><td></td><td style="font-weight:700;text-align:right;">Total</td><td class="right">${fmt(total)}</td></tr>
            </table>
            <p class="fill-line"><b>Amount in words:</b><span class="under">${esc(val('pf-words'))}</span></p>
            <p class="paper-footer">Goods once sold are not returnable.</p>
            <div class="sig-grid">
                <div></div>
                <div><div class="sig-line">Signature</div></div>
            </div>
        `;
    }

    paperEl.innerHTML = header + body;
}

// ===== LOCAL STORAGE =====
function saveToStorage() {
    try {
        const data = {
            activeDoc: state.activeDoc,
            dnRows: state.dn.rows,
            pfRows: state.pf.rows,
            fields: {}
        };
        document.querySelectorAll('input, textarea, select').forEach(el => {
            if (el.id) {
                data.fields[el.id] = el.value;
            }
        });
        ['dn-vat', 'pf-vat'].forEach(id => {
            const cb = $(id);
            if (cb) data.fields[id] = cb.checked;
        });
        localStorage.setItem('fitwell_doc_data', JSON.stringify(data));
    } catch (e) { /* ignore */ }
}

function loadFromStorage() {
    try {
        const raw = localStorage.getItem('fitwell_doc_data');
        if (!raw) return;
        const data = JSON.parse(raw);
        if (data.activeDoc) {
            state.activeDoc = data.activeDoc;
            document.querySelectorAll('.tab').forEach(t => {
                t.classList.toggle('active', t.dataset.doc === data.activeDoc);
            });
            showPanel(data.activeDoc);
        }
        if (data.dnRows) state.dn.rows = data.dnRows;
        if (data.pfRows) state.pf.rows = data.pfRows;
        if (data.fields) {
            Object.keys(data.fields).forEach(id => {
                const el = $(id);
                if (el) {
                    if (el.type === 'checkbox') {
                        el.checked = data.fields[id] === true;
                    } else {
                        el.value = data.fields[id] || '';
                    }
                }
            });
        }
        renderRows('dn');
        renderRows('pf');
        renderPreview();
    } catch (e) { /* ignore */ }
}

// ===== PANEL SWITCHING =====
function showPanel(doc) {
    ['dn', 'rc', 'pf'].forEach(d => {
        const panel = $('panel-' + d);
        if (panel) panel.style.display = (d === doc) ? 'block' : 'none';
    });
}

// ===== TAB CLICK =====
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const doc = this.dataset.doc;
        state.activeDoc = doc;
        showPanel(doc);
        renderPreview();
        saveToStorage();
    });
});

// ===== ADD ROW BUTTONS =====
$('dn-add-row').addEventListener('click', () => addRow('dn'));
$('pf-add-row').addEventListener('click', () => addRow('pf'));

// ===== GLOBAL INPUT CHANGE =====
document.addEventListener('input', function (e) {
    const target = e.target;
    if (target.closest('.row-item')) return;
    renderPreview();
    saveToStorage();
});
document.addEventListener('change', function (e) {
    const target = e.target;
    if (target.id === 'dn-vat' || target.id === 'pf-vat') {
        renderPreview();
        saveToStorage();
    }
});

// ===== TODAY'S DATE =====
function setTodayDates() {
    const today = new Date().toISOString().split('T')[0];
    ['dn-date', 'rc-date', 'pf-date'].forEach(id => {
        const el = $(id);
        if (el && !el.value) el.value = today;
    });
}
$('btn-today').addEventListener('click', setTodayDates);

// ===== RESET CURRENT FORM =====
function resetCurrentForm() {
    const doc = state.activeDoc;
    const panel = $('panel-' + doc);
    if (panel) {
        panel.querySelectorAll('input, textarea, select').forEach(el => {
            if (el.type !== 'checkbox' && el.type !== 'radio') {
                el.value = '';
            } else if (el.type === 'checkbox') {
                el.checked = false;
            }
        });
    }
    if (doc === 'dn' || doc === 'pf') {
        state[doc].rows = getDefaultRows();
        renderRows(doc);
    }
    renderPreview();
    saveToStorage();
}
$('btn-reset').addEventListener('click', resetCurrentForm);

// ===== DUPLICATE CURRENT DOCUMENT =====
function duplicateDocument() {
    const doc = state.activeDoc;
    const noInput = $(doc + '-no');
    if (noInput) {
        const current = parseInt(noInput.value) || 0;
        noInput.value = (current + 1).toString().padStart(3, '0');
    }
    renderPreview();
    saveToStorage();
}
$('btn-duplicate').addEventListener('click', duplicateDocument);

// ===== CLEAR ALL DATA =====
function clearAllData() {
    if (confirm('Delete all saved data and reset to defaults?')) {
        localStorage.removeItem('fitwell_doc_data');
        location.reload();
    }
}
$('btn-clear').addEventListener('click', clearAllData);

// ===== DARK / LIGHT TOGGLE =====
let darkMode = localStorage.getItem('fitwell_dark') === 'true';
function applyDarkMode() {
    document.body.classList.toggle('dark', darkMode);
    localStorage.setItem('fitwell_dark', darkMode);
}
applyDarkMode();
$('btn-dark').addEventListener('click', function () {
    darkMode = !darkMode;
    applyDarkMode();
});

// ===== SAVE BUTTON =====
$('btn-save').addEventListener('click', function () {
    saveToStorage();
    alert('Data saved to local storage.');
});

// ===== PRINT =====
$('btn-print').addEventListener('click', () => window.print());
document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveToStorage();
        alert('Data saved.');
    }
});

// ===== INIT =====
setTodayDates();
loadFromStorage();
renderRows('dn');
renderRows('pf');
renderPreview();