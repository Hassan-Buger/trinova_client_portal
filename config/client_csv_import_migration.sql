-- Production-safe, rerunnable migration for the client CSV module.
-- Existing records are preserved. Run against the selected TriNova database.

CREATE TABLE IF NOT EXISTS entity_contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  needs_contact_details TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_entity_contact_name (entity_id, name),
  INDEX idx_entity_contacts_user (user_id),
  CONSTRAINT fk_entity_contacts_entity FOREIGN KEY (entity_id) REFERENCES client_entities(id) ON DELETE CASCADE,
  CONSTRAINT fk_entity_contacts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MariaDB/MySQL-compatible conditional DDL. This avoids "duplicate column/index"
-- failures when a previous deployment completed only part of the migration.
SET @ddl = IF(
  EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='audit_log' AND column_name='import_metadata'),
  'SELECT 1',
  'ALTER TABLE audit_log ADD COLUMN import_metadata JSON NULL AFTER target_id'
);
PREPARE trinova_stmt FROM @ddl;
EXECUTE trinova_stmt;
DEALLOCATE PREPARE trinova_stmt;

SET @ddl = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='client_entities' AND index_name='idx_entities_company_number'),
  'SELECT 1',
  'ALTER TABLE client_entities ADD INDEX idx_entities_company_number (company_number)'
);
PREPARE trinova_stmt FROM @ddl;
EXECUTE trinova_stmt;
DEALLOCATE PREPARE trinova_stmt;

SET @ddl = IF(
  EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='client_entities' AND index_name='idx_entities_tax_reference'),
  'SELECT 1',
  'ALTER TABLE client_entities ADD INDEX idx_entities_tax_reference (tax_reference)'
);
PREPARE trinova_stmt FROM @ddl;
EXECUTE trinova_stmt;
DEALLOCATE PREPARE trinova_stmt;
