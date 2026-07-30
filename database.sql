-- ============================================================
-- Fitwell Milling Systems – Database Schema (FINAL)
-- ============================================================
DROP DATABASE IF EXISTS fitwell_dms;
CREATE DATABASE IF NOT EXISTS fitwell_dms;
USE fitwell_dms;

-- Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings (company info) – now with pobox, tagline, uploaded logo and
-- per-document brand colors so Delivery Notes / Receipts / Proforma
-- Invoices can each look visually distinct.
CREATE TABLE settings (
    id INT PRIMARY KEY DEFAULT 1,
    company_name VARCHAR(100) NOT NULL DEFAULT 'Your Company Name',
    tagline VARCHAR(150) DEFAULT NULL,
    logo VARCHAR(255) DEFAULT NULL,          -- logo image link (used if no upload)
    logo_data LONGTEXT DEFAULT NULL,          -- uploaded logo, stored as a base64 data URI (takes priority over `logo`)
    address TEXT,
    pobox VARCHAR(50) DEFAULT NULL,
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(100),
    tin VARCHAR(50),
    registration_number VARCHAR(50),
    dn_paper VARCHAR(7) DEFAULT '#EEF3F7',
    dn_ink VARCHAR(7) DEFAULT '#1B2733',
    dn_accent VARCHAR(7) DEFAULT '#2F6690',
    rc_paper VARCHAR(7) DEFAULT '#F2F6EE',
    rc_ink VARCHAR(7) DEFAULT '#1E2A1A',
    rc_accent VARCHAR(7) DEFAULT '#2F6B4F',
    pf_paper VARCHAR(7) DEFAULT '#FBF3E2',
    pf_ink VARCHAR(7) DEFAULT '#2A2013',
    pf_accent VARCHAR(7) DEFAULT '#A5461F',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    company VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    sku VARCHAR(50) UNIQUE,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Delivery Notes
CREATE TABLE delivery_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    date DATE NOT NULL,
    subtotal DECIMAL(15,2) DEFAULT 0.00,
    vat DECIMAL(15,2) DEFAULT 0.00,
    total DECIMAL(15,2) DEFAULT 0.00,
    amount_in_words TEXT,
    delivered_by VARCHAR(100),
    received_by VARCHAR(100),
    created_by_user_id INT DEFAULT NULL,
    created_by_role VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_dn_customer ON delivery_notes(customer_id);
CREATE INDEX idx_dn_number ON delivery_notes(doc_number);

-- Delivery Note Items
CREATE TABLE delivery_note_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_note_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(15,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (delivery_note_id) REFERENCES delivery_notes(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
CREATE INDEX idx_dni_note ON delivery_note_items(delivery_note_id);

-- Receipts
CREATE TABLE receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    amount_in_words TEXT,
    payment_method VARCHAR(50),
    balance DECIMAL(15,2) DEFAULT 0.00,
    description TEXT,
    issued_by VARCHAR(100),
    created_by_user_id INT DEFAULT NULL,
    created_by_role VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_rc_customer ON receipts(customer_id);
CREATE INDEX idx_rc_number ON receipts(doc_number);

-- Proforma Invoices
CREATE TABLE proforma_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doc_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    date DATE NOT NULL,
    subtotal DECIMAL(15,2) DEFAULT 0.00,
    vat DECIMAL(15,2) DEFAULT 0.00,
    total DECIMAL(15,2) DEFAULT 0.00,
    amount_in_words TEXT,
    payment_terms VARCHAR(100),
    contact_info VARCHAR(100),
    created_by_user_id INT DEFAULT NULL,
    created_by_role VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_pf_customer ON proforma_invoices(customer_id);
CREATE INDEX idx_pf_number ON proforma_invoices(doc_number);

-- Proforma Items
CREATE TABLE proforma_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proforma_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(15,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (proforma_id) REFERENCES proforma_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
CREATE INDEX idx_pi_proforma ON proforma_items(proforma_id);

-- ------------------------------------------------------------
-- Insert default admin + staff users
-- IMPORTANT: change these passwords immediately after first login.
--   admin / admin123   (role: admin)
--   staff / staff123   (role: staff)
-- ------------------------------------------------------------
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2b$10$L46EB.y7Vrml90uXgFAVqO6WnKOjCeYYotB7uPwu5ZHik7NTue5om', 'admin'),
('staff', '$2a$10$7rqAsd0JGtdmnHvXwU359OrtR8ggQ3quF5o30OHZD2bUmlKcU2Mae', 'staff');

-- Insert default company settings (generic placeholder — edit in Settings
-- after first login; per-document colors default to the built-in palette
-- via the column defaults above).
INSERT INTO settings (id, company_name, tagline, address, pobox, phone, email, website, tin, registration_number)
VALUES (1, 'Beyond code', 'YOur software solution',
        '', '', '', '', '', '', '');