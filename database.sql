-- ============================================================
-- Fitwell Milling Systems – Database Schema
-- ============================================================
Drop database if exists fitwell_dms;
CREATE DATABASE IF NOT EXISTS fitwell_dms;
USE fitwell_dms;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings (company info)
CREATE TABLE settings (
    id INT PRIMARY KEY DEFAULT 1,
    company_name VARCHAR(100) NOT NULL DEFAULT 'FITWELL MILLING SYSTEMS (U) LIMITED',
    logo VARCHAR(255) DEFAULT NULL,
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(100),
    tin VARCHAR(50),
    registration_number VARCHAR(50),
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
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

ALTER TABLE settings ADD COLUMN pobox VARCHAR(50) DEFAULT '9021, Kampala, Uganda' AFTER address;
-- ------------------------------------------------------------
-- Insert default admin user (password: admin123)
-- The hash below is generated with password_hash('admin123', PASSWORD_DEFAULT)
-- ------------------------------------------------------------
INSERT INTO users (username, password_hash, role) VALUES 
('admin', '$2a$10$wybj/NXyuiQlz0WFvpEZlu3FFSJlDvS49OZKXzlZpMRCgNblYW5Xq', 'admin');

-- Insert default company settings
INSERT INTO settings (id, company_name, address, phone, email, website, tin, registration_number)
VALUES (1, 'FITWELL MILLING SYSTEMS (U) LIMITED', 'Plot 14, Kifumbira Rd, Ntinda, Kampala', '+256 701 220345', 'info@fitwellmilling.co.ug', 'www.fitwellmilling.co.ug', '1012345678', '80020001234567');