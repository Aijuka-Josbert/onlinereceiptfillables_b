-- ============================================================
-- Migration: run this ONLY if you already have a live database
-- (i.e. you are NOT running the fresh database.sql). This adds
-- the new columns/tables without touching existing data.
--
-- If a statement errors with "Duplicate column name" or
-- "Table already exists", that piece was already applied —
-- just skip that line and continue with the rest.
-- ============================================================
USE fitwell_dms;  -- change this if your database has a different name

-- Settings: tagline, uploaded logo, per-document brand colors
ALTER TABLE settings ADD COLUMN tagline VARCHAR(150) DEFAULT NULL AFTER company_name;
ALTER TABLE settings ADD COLUMN logo_data LONGTEXT DEFAULT NULL AFTER logo;
ALTER TABLE settings ADD COLUMN dn_paper VARCHAR(7) DEFAULT '#EEF3F7';
ALTER TABLE settings ADD COLUMN dn_ink VARCHAR(7) DEFAULT '#1B2733';
ALTER TABLE settings ADD COLUMN dn_accent VARCHAR(7) DEFAULT '#2F6690';
ALTER TABLE settings ADD COLUMN rc_paper VARCHAR(7) DEFAULT '#F2F6EE';
ALTER TABLE settings ADD COLUMN rc_ink VARCHAR(7) DEFAULT '#1E2A1A';
ALTER TABLE settings ADD COLUMN rc_accent VARCHAR(7) DEFAULT '#2F6B4F';
ALTER TABLE settings ADD COLUMN pf_paper VARCHAR(7) DEFAULT '#FBF3E2';
ALTER TABLE settings ADD COLUMN pf_ink VARCHAR(7) DEFAULT '#2A2013';
ALTER TABLE settings ADD COLUMN pf_accent VARCHAR(7) DEFAULT '#A5461F';

-- Make sure a settings row actually exists (fresh ALTERs are no-op if empty)
INSERT IGNORE INTO settings (id, company_name) VALUES (1, 'Your Company Name');

-- Delivery notes / receipts / proforma invoices: record which logged-in
-- user (and role) created each document, so records are attributable
-- to a specific staff/admin account rather than a free-typed name.
ALTER TABLE delivery_notes ADD COLUMN created_by_user_id INT DEFAULT NULL;
ALTER TABLE delivery_notes ADD COLUMN created_by_role VARCHAR(10) DEFAULT NULL;
ALTER TABLE delivery_notes ADD CONSTRAINT fk_dn_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE receipts ADD COLUMN created_by_user_id INT DEFAULT NULL;
ALTER TABLE receipts ADD COLUMN created_by_role VARCHAR(10) DEFAULT NULL;
ALTER TABLE receipts ADD CONSTRAINT fk_rc_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE proforma_invoices ADD COLUMN created_by_user_id INT DEFAULT NULL;
ALTER TABLE proforma_invoices ADD COLUMN created_by_role VARCHAR(10) DEFAULT NULL;
ALTER TABLE proforma_invoices ADD CONSTRAINT fk_pf_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- If your `users` table still has the old admin row with an invalid
-- password hash and you can't log in, reset it here (password: admin123):
-- UPDATE users SET password_hash = '$2b$10$L46EB.y7Vrml90uXgFAVqO6WnKOjCeYYotB7uPwu5ZHik7NTue5om' WHERE username = 'admin';