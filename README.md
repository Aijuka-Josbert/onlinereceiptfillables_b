# 📄 Fitwell Milling Systems – Document Generator

A professional, responsive, single‑page web application for creating **Delivery Notes**, **Receipts**, and **Proforma Invoices** with a live preview, automatic calculations, and print‑ready output. Built with vanilla HTML, CSS, and JavaScript – no frameworks required.

![Preview](https://via.placeholder.com/800x450?text=Fitwell+Document+Generator+Preview)

---

## 🚀 Live Demo

> Hosted on **GitHub Pages** – [Insert your live URL here once deployed]

---

## ✨ Features

- **Three Document Types** – Switch seamlessly between Delivery Note, Receipt, and Proforma Invoice.
- **Live Preview** – The document updates instantly as you type; no refresh needed.
- **Automatic Calculations** – `Quantity × Unit Price` → Amount → Subtotal → VAT (optional 18%) → Grand Total with thousands separators.
- **Amount in Words** – Auto‑generated from the grand total (or amount for receipts).
- **Item Management** – Add unlimited rows, remove rows, auto‑calculate amounts per row.
- **Company Letterhead** – Edit company name, logo, address, P.O. Box, telephone, email, TIN, registration number, and website – all reflected across documents.
- **Print / Save as PDF** – One‑click printing with clean, color‑preserved formatting (sidebar hidden).
- **Local Storage** – All data (company details, customer info, items, VAT selection) is auto‑saved and restored on browser reopen.
- **Dark / Light Mode** – Toggle themes for comfortable day or night use.
- **Keyboard Shortcuts** – `Ctrl+P` (print), `Ctrl+S` (save to Local Storage).
- **Responsive Design** – Optimised for desktop, tablet, and mobile.
- **Font Awesome Icons** – Clean, professional icons throughout the interface.

---

## 🛠️ Technologies Used

- **HTML5** – Semantic structure  
- **CSS3** – Custom properties (variables) for theming, responsive layout, and print styles  
- **Vanilla JavaScript** – All logic, state management, DOM manipulation, and storage  
- **Google Fonts** – *Arvo*, *Courier Prime*, and *Inter* for a refined typography  
- **Font Awesome 6** – Icons for buttons and labels  
- **Local Storage** – Persistent data saving  

No external frameworks, libraries, or build tools are required – the entire application runs in the browser.

---

## 📁 Project Structure

```
/
├── index.html               # Main HTML file
├── assets/
│   ├── css/
│   │   └── style.css        # All styles (light/dark, print, responsive)
│   └── js/
│       └── script.js        # All JavaScript logic
└── README.md                # This file
```

---

## 🔧 Installation & Setup

### Option 1 – Local Usage
1. Download or clone the repository.
2. Place the three files (`index.html`, `style.css`, `script.js`) in the appropriate folder structure as shown above.
3. Open `index.html` in any modern web browser (Chrome, Firefox, Edge, etc.).
4. That’s it – no server required.

### Option 2 – Host on GitHub Pages
1. Push the repository to GitHub.
2. Go to your repository **Settings** → **Pages**.
3. Select the branch (e.g., `main`) and root folder.
4. Save – your site will be live at `https://<username>.github.io/<repository>/`.

---

## 🧑‍💻 Usage Guide

1. **Fill the Sidebar** – Enter company letterhead details, client information, and items.
2. **Switch Document Types** – Click on the tabs to switch between Delivery Note, Receipt, or Proforma Invoice.
3. **Add / Remove Items** – Use the *“+ Add item”* button and the *“Remove”* button per row. Quantities and rates automatically calculate amounts.
4. **Toggle VAT** – Check the box to apply 18% VAT to the subtotal.
5. **Watch the Preview** – The document on the right updates live.
6. **Use the Action Buttons**:
   - **Today** – Auto‑fill today’s date in all date fields.
   - **Reset** – Clear the currently active document’s fields (keeps letterhead).
   - **Duplicate** – Increments the document number and keeps the same data.
   - **Clear All** – Delete all saved data and reset to defaults.
   - **Dark / Light** – Switch themes.
   - **Save** – Manually save to Local Storage (auto‑save happens on every input).
7. **Print / Save as PDF** – Click the golden button or press `Ctrl+P`. The print view hides the sidebar and controls, preserving the document’s colors and margins.

---

## 🎨 Customisation

- **Company Details** – Edit the fields in the *Letterhead* section. All changes are reflected in every document.
- **Logo** – Paste a direct URL to an image in the *Company Logo (URL)* field.
- **Styling** – Adjust CSS variables in `style.css` under the `:root` and `body.dark` selectors to match your brand colours.

---

## ⌨️ Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl + P` | Open print dialog (document only) |
| `Ctrl + S` | Save current data to Local Storage |

---

## 💾 Local Storage

All data is automatically saved to the browser’s Local Storage whenever you type, change a checkbox, add/remove rows, or switch documents. Upon reopening the page, everything is restored – including the active document type, all fields, items, and VAT selection.

---

## 🌓 Dark / Light Mode

Toggle between light and dark themes using the *Dark / Light* button. Your preference is stored in Local Storage and persists across sessions.

---

## 🖨️ Print & PDF

Click the *Print / Save as PDF* button or press `Ctrl+P`. The print styles:
- Hide the sidebar and all controls
- Preserve the paper background colour, text colours, table headers, and stamp colour (using `print-color-adjust: exact`)
- Maintain professional margins and font styling

---

## 🤝 Contributing

Contributions are welcome! If you have suggestions or find issues, please open an issue or submit a pull request.

1. Fork the repository
2. Create a new branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -am 'Add your feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request

---

## 📄 License


---

## 🙏 Acknowledgements

- [Font Awesome](https://fontawesome.com/) for the beautiful icons
- [Google Fonts](https://fonts.google.com/) for the typefaces

---

