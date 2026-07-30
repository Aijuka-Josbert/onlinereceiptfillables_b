/**
 * ============================================================
 *  Document Generator — Delivery Note / Receipt / Proforma Invoice
 *  Vanilla JavaScript — state, rendering, theming, storage
 * ============================================================
 */

// ===== COLOR PALETTES (used for both the swatch picker and defaults) =====
const PALETTES = [
  { name: "Slate Blue", paper: "#EEF3F7", ink: "#1B2733", accent: "#2F6690" },
  { name: "Forest Green", paper: "#F2F6EE", ink: "#1E2A1A", accent: "#2F6B4F" },
  { name: "Amber Gold", paper: "#FBF3E2", ink: "#2A2013", accent: "#A5461F" },
  {
    name: "Charcoal Mono",
    paper: "#F4F4F2",
    ink: "#1A1A1A",
    accent: "#1A1A1A",
  },
  { name: "Burgundy", paper: "#F8EEEC", ink: "#2B1414", accent: "#7A2331" },
  { name: "Deep Teal", paper: "#EAF5F3", ink: "#132420", accent: "#0F6F60" },
  { name: "Royal Plum", paper: "#F4EEF6", ink: "#231A2B", accent: "#6A3E82" },
  {
    name: "Classic Cream",
    paper: "#F8F3E9",
    ink: "#1E1B16",
    accent: "#A53F2F",
  },
];

// Default palette index assigned to each document type, so all three
// look distinct out of the box, before any user customization.
const DEFAULT_PALETTE_INDEX = { dn: 0, rc: 1, pf: 2 };

// ===== STATE =====
const state = {
  activeDoc: "dn",
  dn: { rows: [] },
  rc: {},
  pf: { rows: [] },
  themes: {}, // filled by initThemes()
  logo: { dataUrl: "", url: "" },
};
function getDefaultRows() {
  return [{ qty: "", desc: "", rate: "", amt: "" }];
}
state.dn.rows = getDefaultRows();
state.pf.rows = getDefaultRows();

function initThemes() {
  ["dn", "rc", "pf"].forEach((doc) => {
    const p = PALETTES[DEFAULT_PALETTE_INDEX[doc]];
    state.themes[doc] = { paper: p.paper, ink: p.ink, accent: p.accent };
  });
}
initThemes();

// ===== DOM REFS =====
const $ = (id) => document.getElementById(id);
const paperEl = $("paper");

// ===== COLOR MATH (derive soft-ink / rule tints from paper + ink) =====
function hexToRgb(hex) {
  hex = (hex || "#000000").replace("#", "");
  if (hex.length === 3)
    hex = hex
      .split("")
      .map((c) => c + c)
      .join("");
  const n = parseInt(hex, 16);
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}
function rgbToHex(rgb) {
  return (
    "#" +
    rgb
      .map((v) =>
        Math.max(0, Math.min(255, Math.round(v)))
          .toString(16)
          .padStart(2, "0"),
      )
      .join("")
  );
}
function mix(hexA, hexB, t) {
  const a = hexToRgb(hexA),
    b = hexToRgb(hexB);
  return rgbToHex(a.map((v, i) => v + (b[i] - v) * t));
}

// ===== UTILITY FUNCTIONS =====
function num(v) {
  const n = parseFloat((v || "").toString().replace(/,/g, ""));
  return isNaN(n) ? 0 : n;
}
function fmt(n) {
  return n ? n.toLocaleString("en-US") : "";
}
function esc(s) {
  return (s || "").replace(/&/g, "&amp;").replace(/</g, "&lt;");
}
function val(id) {
  return $(id) ? $(id).value : "";
}

// ===== NUMBER TO WORDS CONVERTER =====
function numberToWords(n) {
  if (n === 0) return "Zero";
  const numStr = n.toFixed(2);
  const parts = numStr.split(".");
  const wholePart = parseInt(parts[0]);
  const decimalPart = parseInt(parts[1]);

  const units = [
    "",
    "One",
    "Two",
    "Three",
    "Four",
    "Five",
    "Six",
    "Seven",
    "Eight",
    "Nine",
  ];
  const teens = [
    "Ten",
    "Eleven",
    "Twelve",
    "Thirteen",
    "Fourteen",
    "Fifteen",
    "Sixteen",
    "Seventeen",
    "Eighteen",
    "Nineteen",
  ];
  const tens = [
    "",
    "",
    "Twenty",
    "Thirty",
    "Forty",
    "Fifty",
    "Sixty",
    "Seventy",
    "Eighty",
    "Ninety",
  ];
  const scales = ["", "Thousand", "Million", "Billion", "Trillion"];

  function convertChunk(chunkNum) {
    let words = "";
    const hundreds = Math.floor(chunkNum / 100);
    const rest = chunkNum % 100;
    if (hundreds > 0) words += units[hundreds] + " Hundred ";
    if (rest > 0) {
      if (rest < 10) words += units[rest] + " ";
      else if (rest < 20) words += teens[rest - 10] + " ";
      else {
        const ten = Math.floor(rest / 10),
          unit = rest % 10;
        words += tens[ten] + " ";
        if (unit > 0) words += units[unit] + " ";
      }
    }
    return words;
  }

  let words = "";
  let n2 = wholePart;
  let scaleIndex = 0;
  while (n2 > 0) {
    const chunk = n2 % 1000;
    if (chunk !== 0)
      words = convertChunk(chunk) + scales[scaleIndex] + " " + words;
    n2 = Math.floor(n2 / 1000);
    scaleIndex++;
  }
  if (decimalPart > 0) words += "and " + decimalPart + "/100";
  return words.trim();
}

// ===== THEME (per-document colors) =====
function applyTheme(doc) {
  const t = state.themes[doc];
  paperEl.style.setProperty("--doc-paper", t.paper);
  paperEl.style.setProperty("--doc-ink", t.ink);
  paperEl.style.setProperty("--doc-ink-soft", mix(t.ink, t.paper, 0.45));
  paperEl.style.setProperty("--doc-stamp", t.accent);
  paperEl.style.setProperty("--doc-rule", mix(t.ink, t.paper, 0.82));

  // Sync the color pickers + active swatch highlight for this doc
  const paperInput = $(doc + "-c-paper");
  const inkInput = $(doc + "-c-ink");
  const accentInput = $(doc + "-c-accent");
  if (paperInput) paperInput.value = t.paper;
  if (inkInput) inkInput.value = t.ink;
  if (accentInput) accentInput.value = t.accent;

  document
    .querySelectorAll(`.swatch-row[data-doc="${doc}"] .swatch`)
    .forEach((sw) => {
      const idx = parseInt(sw.dataset.paletteIndex, 10);
      const p = PALETTES[idx];
      const matches =
        p.paper.toLowerCase() === t.paper.toLowerCase() &&
        p.ink.toLowerCase() === t.ink.toLowerCase() &&
        p.accent.toLowerCase() === t.accent.toLowerCase();
      sw.classList.toggle("active", matches);
    });
}
function setThemeColor(doc, key, value) {
  state.themes[doc][key] = value;
  if (doc === state.activeDoc) applyTheme(doc);
  saveToStorage();
}
function buildSwatches() {
  ["dn", "rc", "pf"].forEach((doc) => {
    const row = document.querySelector(`.swatch-row[data-doc="${doc}"]`);
    if (!row) return;
    row.innerHTML = "";
    PALETTES.forEach((p, idx) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "swatch";
      btn.title = p.name;
      btn.style.background = p.paper;
      btn.style.boxShadow = `inset 0 0 0 3px ${p.accent}`;
      btn.dataset.paletteIndex = idx;
      btn.addEventListener("click", () => {
        state.themes[doc] = { paper: p.paper, ink: p.ink, accent: p.accent };
        applyTheme(doc);
        saveToStorage();
      });
      row.appendChild(btn);
    });
  });

  document
    .querySelectorAll('.color-pick input[type="color"]')
    .forEach((inp) => {
      const [doc, , key] = inp.id.split("-"); // e.g. "dn-c-paper" -> dn, c, paper
      const propMap = { paper: "paper", ink: "ink", accent: "accent" };
      inp.addEventListener("input", () =>
        setThemeColor(doc, propMap[key], inp.value),
      );
    });

  document.querySelectorAll(".theme-reset").forEach((btn) => {
    btn.addEventListener("click", () => {
      const doc = btn.dataset.doc;
      const p = PALETTES[DEFAULT_PALETTE_INDEX[doc]];
      state.themes[doc] = { paper: p.paper, ink: p.ink, accent: p.accent };
      applyTheme(doc);
      saveToStorage();
    });
  });
}

// ===== LOGO (upload or link) =====
function currentLogoSrc() {
  return state.logo.dataUrl || state.logo.url || "";
}
function refreshLogoPreview() {
  const src = currentLogoSrc();
  const row = $("logo-preview-row");
  const img = $("logo-preview");
  if (src) {
    img.src = src;
    row.hidden = false;
  } else {
    row.hidden = true;
  }
}
function initLogoControls() {
  $("hd-logo-file").addEventListener("change", function (e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith("image/")) {
      alert("Please choose an image file.");
      return;
    }
    const reader = new FileReader();
    reader.onload = function (ev) {
      state.logo.dataUrl = ev.target.result;
      refreshLogoPreview();
      renderPreview();
      saveToStorage();
    };
    reader.readAsDataURL(file);
  });
  $("hd-logo-url").addEventListener("input", function () {
    state.logo.url = this.value.trim();
    refreshLogoPreview();
    renderPreview();
    saveToStorage();
  });
  $("hd-logo-clear").addEventListener("click", function () {
    state.logo.dataUrl = "";
    state.logo.url = "";
    $("hd-logo-file").value = "";
    $("hd-logo-url").value = "";
    refreshLogoPreview();
    renderPreview();
    saveToStorage();
  });
}

// ===== RENDER ITEM ROWS (sidebar) =====
function renderRows(docKey) {
  const container = $(docKey + "-rows");
  if (!container) return;
  const rows = state[docKey].rows;
  container.innerHTML = "";
  rows.forEach((r, idx) => {
    const div = document.createElement("div");
    div.className = "row-item";
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
            <button class="remove-row" type="button" data-doc="${docKey}" data-idx="${idx}">Remove</button>
        `;
    container.appendChild(div);
  });
  container
    .querySelectorAll("input")
    .forEach((inp) => inp.addEventListener("input", onRowInput));
  container
    .querySelectorAll(".remove-row")
    .forEach((btn) => btn.addEventListener("click", onRemoveRow));
}

// ===== ROW EVENT HANDLERS =====
function onRowInput(e) {
  const inp = e.target;
  const doc = inp.dataset.doc;
  const idx = parseInt(inp.dataset.idx, 10);
  const field = inp.dataset.field;
  if (state[doc] && state[doc].rows[idx] !== undefined) {
    state[doc].rows[idx][field] = inp.value;
    if (field === "qty" || field === "rate") {
      const q = num(state[doc].rows[idx].qty);
      const rate = num(state[doc].rows[idx].rate);
      const amt = q && rate ? q * rate : 0;
      state[doc].rows[idx].amt = amt ? fmt(amt) : "";
      const rowDiv = inp.closest(".row-item");
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
  const idx = parseInt(btn.dataset.idx, 10);
  if (state[doc] && state[doc].rows.length > 1) {
    state[doc].rows.splice(idx, 1);
    renderRows(doc);
    renderPreview();
    saveToStorage();
  } else {
    alert("You need at least one item row.");
  }
}
function addRow(docKey) {
  state[docKey].rows.push({ qty: "", desc: "", rate: "", amt: "" });
  renderRows(docKey);
  renderPreview();
  saveToStorage();
}

// ===== PREVIEW RENDER =====
function renderHeader() {
  const company = esc(val("hd-name")) || "Your Company Name";
  const tagline = esc(val("hd-tagline"));
  const addr = esc(val("hd-addr"));
  const pobox = esc(val("hd-pobox"));
  const phone = esc(val("hd-phone"));
  const email = esc(val("hd-email"));
  const tin = esc(val("hd-tin"));
  const reg = esc(val("hd-reg"));
  const web = esc(val("hd-web"));
  const logo = currentLogoSrc();

  return `
        ${logo ? `<img class="co-logo" src="${logo}" alt="${company} logo" />` : ""}
        <p class="co-name">${company}</p>
        ${tagline ? `<p class="co-tag">${tagline}</p>` : ""}
        <hr class="hr">
        <div class="addr-block">
            ${addr ? `<p>${addr}</p>` : ""}
            ${pobox ? `<p>P.O. Box: ${pobox}</p>` : ""}
            ${phone ? `<p>Tel: ${phone}</p>` : ""}
            ${email ? `<p>Email: ${email}</p>` : ""}
            ${tin ? `<p>TIN: ${tin}</p>` : ""}
            ${reg ? `<p>Reg. No.: ${reg}</p>` : ""}
            ${web ? `<p>Web: ${web}</p>` : ""}
        </div>
    `;
}

function renderPreview() {
  const header = renderHeader();
  let body = "";
  const active = state.activeDoc;

  if (active === "rc") {
    const amount = num(val("rc-amt"));
    const wordsInput = $("rc-words");
    if (wordsInput) wordsInput.value = numberToWords(amount);
    body = `
            <div class="title-box"><span>RECEIPT</span></div>
            <div class="meta-row">
                <span>No. ${esc(val("rc-no"))}</span>
                <span>Date: ${esc(val("rc-date")) || "____/____/____"}</span>
            </div>
            <p class="fill-line"><b>Received with thanks from:</b><span class="under">${esc(val("rc-from"))}</span></p>
            <p class="fill-line"><b>Being payment of:</b><span class="under">${esc(val("rc-for"))}</span></p>
            <p class="fill-line"><b>Payment Method:</b><span class="under">${esc(val("rc-method"))}</span></p>
            <p class="fill-line"><b>Amount in words:</b><span class="under">${esc(val("rc-words"))}</span></p>
            <p class="fill-line">
                <b>Cash / Cheque No.:</b><span class="under">${esc(val("rc-cash"))}</span>
                <b style="margin-left:20px;">Balance:</b><span class="under">${esc(val("rc-bal"))}</span>
            </p>
            <p class="fill-line" style="font-size:16px;font-weight:700;">
                <b>Amount (UGX/USD):</b><span class="under">${esc(val("rc-amt"))}</span>
            </p>
            <div class="sig-grid">
                <div><div class="sig-line">Issued by: ${esc(val("rc-issued"))}</div></div>
                <div><div class="sig-line">Signature</div></div>
            </div>
        `;
  } else if (active === "dn") {
    const applyVat = $("dn-vat").checked;
    const rows = state.dn.rows.map((r) => {
      const q = num(r.qty),
        rate = num(r.rate);
      const amt = r.amt ? num(r.amt) : q && rate ? q * rate : 0;
      return { ...r, amt };
    });
    const subtotal = rows.reduce((s, r) => s + (r.amt || 0), 0);
    const vat = applyVat ? subtotal * 0.18 : 0;
    const total = subtotal + vat;

    const wordsInput = $("dn-words");
    if (wordsInput) wordsInput.value = numberToWords(total);

    const padded = rows.slice();
    while (padded.length < 8)
      padded.push({ qty: "", desc: "", rate: "", amt: 0 });
    const rowsHtml = padded
      .map(
        (r) => `
            <tr>
                <td class="num">${esc(r.qty) || "&nbsp;"}</td>
                <td>${esc(r.desc) || "&nbsp;"}</td>
                <td class="right">${r.rate ? esc(r.rate) : "&nbsp;"}</td>
                <td class="right">${r.amt ? fmt(r.amt) : "&nbsp;"}</td>
            </tr>
        `,
      )
      .join("");
    body = `
            <div class="title-box"><span>DELIVERY NOTE</span></div>
            <div class="meta-row">
                <span>No. ${esc(val("dn-no"))}</span>
                <span>Date: ${esc(val("dn-date")) || "____/____/____"}</span>
            </div>
            <p class="client-row"><b>Client name:</b> ${esc(val("dn-client")) || "&nbsp;"}</p>
            <p class="client-row" style="font-size:12px;"><b>Address:</b> ${esc(val("dn-client-addr")) || "&nbsp;"}</p>
            <div style="display:flex;gap:20px;font-size:12px;margin-bottom:12px;">
                <span><b>Contact:</b> ${esc(val("dn-contact")) || "&nbsp;"}</span>
                <span><b>Phone:</b> ${esc(val("dn-phone")) || "&nbsp;"}</span>
            </div>
            <table class="doc-table">
                <tr><th style="width:12%">Qty</th><th style="width:48%">Particulars</th><th style="width:20%">Rate (UGX/USD)</th><th style="width:20%">Amount (UGX/USD)</th></tr>
                ${rowsHtml}
                <tr><td>E.&amp;O.E</td><td></td><td style="font-weight:700;text-align:right;">Sub total</td><td class="right">${fmt(subtotal)}</td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">18% VAT</td><td class="right">${fmt(vat)}</td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">Total</td><td class="right">${fmt(total)}</td></tr>
            </table>
            <p class="fill-line"><b>Amount in words:</b><span class="under">${esc(val("dn-words"))}</span></p>
            <div class="sig-grid">
                <div><div class="sig-line">Delivered by: ${esc(val("dn-delby"))}</div></div>
                <div><div class="sig-line">Received by: ${esc(val("dn-recby"))}</div></div>
            </div>
        `;
  } else if (active === "pf") {
    const applyVat = $("pf-vat").checked;
    const rows = state.pf.rows.map((r) => {
      const q = num(r.qty),
        rate = num(r.rate);
      const amt = r.amt ? num(r.amt) : q && rate ? q * rate : 0;
      return { ...r, amt };
    });
    const subtotal = rows.reduce((s, r) => s + (r.amt || 0), 0);
    const vat = applyVat ? subtotal * 0.18 : 0;
    const total = subtotal + vat;

    const wordsInput = $("pf-words");
    if (wordsInput) wordsInput.value = numberToWords(total);

    const padded = rows.slice();
    while (padded.length < 6)
      padded.push({ qty: "", desc: "", rate: "", amt: 0 });
    const rowsHtml = padded
      .map(
        (r) => `
            <tr>
                <td class="num">${esc(r.qty) || "&nbsp;"}</td>
                <td>${esc(r.desc) || "&nbsp;"}</td>
                <td class="right">${r.rate ? esc(r.rate) : "&nbsp;"}</td>
                <td class="right">${r.amt ? fmt(r.amt) : "&nbsp;"}</td>
            </tr>
        `,
      )
      .join("");
    body = `
            <div class="title-box"><span>PROFORMA INVOICE</span></div>
            <div class="meta-row">
                <span>No. ${esc(val("pf-no"))}</span>
                <span>Date: ${esc(val("pf-date")) || "____/____/____"}</span>
            </div>
            <p class="client-row"><b>M/s:</b> ${esc(val("pf-client")) || "&nbsp;"}</p>
            <p class="client-row" style="font-size:12px;"><b>Address:</b> ${esc(val("pf-client-addr")) || "&nbsp;"}</p>
            <table class="doc-table">
                <tr><th style="width:14%">Qty</th><th style="width:46%">Particulars</th><th style="width:20%">Rate</th><th style="width:20%">Amount</th></tr>
                ${rowsHtml}
                <tr><td></td><td style="font-size:10.5px;">Terms: ${esc(val("pf-terms"))}. Contact: ${esc(val("pf-contact"))}</td><td style="font-weight:700;text-align:right;">Sub total</td><td class="right">${fmt(subtotal)}</td></tr>
                <tr><td></td><td></td><td style="font-weight:700;text-align:right;">18% VAT</td><td class="right">${fmt(vat)}</td></tr>
                <tr><td>E.&amp;O.E</td><td></td><td style="font-weight:700;text-align:right;">Total</td><td class="right">${fmt(total)}</td></tr>
            </table>
            <p class="fill-line"><b>Amount in words:</b><span class="under">${esc(val("pf-words"))}</span></p>
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
const STORAGE_KEY = "docgen_data";
const DARK_KEY = "docgen_dark";

function saveToStorage() {
  try {
    const data = {
      activeDoc: state.activeDoc,
      dnRows: state.dn.rows,
      pfRows: state.pf.rows,
      themes: state.themes,
      logo: state.logo,
      fields: {},
    };
    document.querySelectorAll("input, textarea, select").forEach((el) => {
      if (el.id && el.type !== "file" && el.type !== "color") {
        data.fields[el.id] = el.value;
      }
    });
    ["dn-vat", "pf-vat"].forEach((id) => {
      const cb = $(id);
      if (cb) data.fields[id] = cb.checked;
    });
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  } catch (e) {
    /* storage may be full or unavailable — ignore */
  }
}

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return;
    const data = JSON.parse(raw);

    if (data.themes) {
      ["dn", "rc", "pf"].forEach((doc) => {
        if (data.themes[doc]) state.themes[doc] = data.themes[doc];
      });
    }
    if (data.logo) state.logo = data.logo;

    if (data.activeDoc) {
      state.activeDoc = data.activeDoc;
      document.querySelectorAll(".tab").forEach((t) => {
        const isActive = t.dataset.doc === data.activeDoc;
        t.classList.toggle("active", isActive);
        t.setAttribute("aria-selected", isActive ? "true" : "false");
      });
      showPanel(data.activeDoc);
    }
    if (data.dnRows) state.dn.rows = data.dnRows;
    if (data.pfRows) state.pf.rows = data.pfRows;
    if (data.fields) {
      Object.keys(data.fields).forEach((id) => {
        const el = $(id);
        if (el) {
          if (el.type === "checkbox") el.checked = data.fields[id] === true;
          else el.value = data.fields[id] || "";
        }
      });
    }

    renderRows("dn");
    renderRows("pf");
    refreshLogoPreview();
    applyTheme(state.activeDoc);
    renderPreview();
  } catch (e) {
    /* ignore corrupt storage */
  }
}

// ===== PANEL SWITCHING =====
function showPanel(doc) {
  ["rc", "dn", "pf"].forEach((d) => {
    const panel = $("panel-" + d);
    if (panel) panel.style.display = d === doc ? "block" : "none";
  });
}

// ===== TAB CLICK =====
document.querySelectorAll(".tab").forEach((tab) => {
  tab.addEventListener("click", function () {
    document.querySelectorAll(".tab").forEach((t) => {
      t.classList.remove("active");
      t.setAttribute("aria-selected", "false");
    });
    this.classList.add("active");
    this.setAttribute("aria-selected", "true");
    const doc = this.dataset.doc;
    state.activeDoc = doc;
    showPanel(doc);
    applyTheme(doc);
    renderPreview();
    saveToStorage();
  });
});

// ===== ADD ROW BUTTONS =====
$("dn-add-row").addEventListener("click", () => addRow("dn"));
$("pf-add-row").addEventListener("click", () => addRow("pf"));

// ===== GLOBAL INPUT CHANGE =====
document.addEventListener("input", function (e) {
  const target = e.target;
  if (target.closest(".row-item")) return;
  if (target.type === "color" || target.type === "file") return; // handled separately
  if (target.id === "hd-logo-url") return; // handled separately
  renderPreview();
  saveToStorage();
});
document.addEventListener("change", function (e) {
  const target = e.target;
  if (target.id === "dn-vat" || target.id === "pf-vat") {
    renderPreview();
    saveToStorage();
  }
});

// ===== TODAY'S DATE =====
function setTodayDates() {
  const today = new Date().toISOString().split("T")[0];
  ["rc-date", "dn-date", "pf-date"].forEach((id) => {
    const el = $(id);
    if (el && !el.value) el.value = today;
  });
}
$("btn-today").addEventListener("click", () => {
  setTodayDates();
  renderPreview();
  saveToStorage();
});

// ===== RESET CURRENT FORM =====
function resetCurrentForm() {
  const doc = state.activeDoc;
  const panel = $("panel-" + doc);
  if (panel) {
    panel.querySelectorAll("input, textarea, select").forEach((el) => {
      if (el.type === "color") return;
      if (el.type === "checkbox" || el.type === "radio") el.checked = false;
      else el.value = "";
    });
  }
  if (doc === "dn" || doc === "pf") {
    state[doc].rows = getDefaultRows();
    renderRows(doc);
  }
  renderPreview();
  saveToStorage();
}
$("btn-reset").addEventListener("click", resetCurrentForm);

// ===== DUPLICATE CURRENT DOCUMENT =====
function duplicateDocument() {
  const doc = state.activeDoc;
  const noInput = $(doc + "-no");
  if (noInput) {
    const current = parseInt(noInput.value, 10) || 0;
    noInput.value = (current + 1).toString().padStart(3, "0");
  }
  renderPreview();
  saveToStorage();
}
$("btn-duplicate").addEventListener("click", duplicateDocument);

// ===== CLEAR ALL DATA =====
function clearAllData() {
  if (
    confirm(
      "Delete all saved data (including logo and colors) and reset to defaults?",
    )
  ) {
    localStorage.removeItem(STORAGE_KEY);
    location.reload();
  }
}
$("btn-clear").addEventListener("click", clearAllData);

// ===== DARK / LIGHT UI TOGGLE (app chrome only — document colors are separate) =====
let darkMode = localStorage.getItem(DARK_KEY) === "true";
function applyDarkMode() {
  document.body.classList.toggle("dark", darkMode);
  localStorage.setItem(DARK_KEY, darkMode);
}
applyDarkMode();
$("btn-dark").addEventListener("click", function () {
  darkMode = !darkMode;
  applyDarkMode();
});

// ===== SAVE BUTTON =====
$("btn-save").addEventListener("click", function () {
  saveToStorage();
  alert("Data saved to this browser.");
});

// ===== PRINT =====
$("btn-print").addEventListener("click", () => window.print());
document.addEventListener("keydown", function (e) {
  if ((e.ctrlKey || e.metaKey) && e.key === "p") {
    e.preventDefault();
    window.print();
  }
  if ((e.ctrlKey || e.metaKey) && e.key === "s") {
    e.preventDefault();
    saveToStorage();
    alert("Data saved.");
  }
});

// ===== INIT =====
buildSwatches();
initLogoControls();
setTodayDates();
loadFromStorage();
renderRows("dn");
renderRows("pf");
refreshLogoPreview();
applyTheme(state.activeDoc);
renderPreview();
