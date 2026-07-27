-- Company records with multiple directors and scoped client data.
-- Run once after taking a database backup.

ALTER TABLE client_entities
  ADD COLUMN entity_scope ENUM('company','personal') NOT NULL DEFAULT 'company' AFTER entity_type;

UPDATE client_entities
SET entity_scope = CASE
  WHEN LOWER(entity_type) LIKE '%personal%' OR LOWER(entity_type) LIKE '%individual%'
    THEN 'personal'
  ELSE 'company'
END;

-- Give every existing client a safe personal container before backfilling legacy records.
INSERT INTO client_entities (client_id, company_name, entity_type, entity_scope, attributes)
SELECT c.id, CONCAT(u.name, ' - Personal Record'), 'Personal Tax Return', 'personal', JSON_OBJECT()
FROM clients c JOIN users u ON u.id=c.user_id
WHERE NOT EXISTS (SELECT 1 FROM client_entities existing WHERE existing.client_id=c.id);

CREATE TABLE IF NOT EXISTS entity_directors (
  entity_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_by_user_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (entity_id, user_id),
  INDEX idx_entity_directors_user (user_id, entity_id),
  CONSTRAINT fk_entity_directors_entity FOREIGN KEY (entity_id) REFERENCES client_entities(id) ON DELETE CASCADE,
  CONSTRAINT fk_entity_directors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_entity_directors_creator FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO entity_directors (entity_id, user_id)
SELECT e.id, c.user_id
FROM client_entities e
JOIN clients c ON c.id = e.client_id
WHERE e.entity_scope = 'company';

ALTER TABLE documents
  ADD COLUMN entity_id INT UNSIGNED NULL AFTER client_id,
  ADD COLUMN scope ENUM('company','personal') NOT NULL DEFAULT 'company' AFTER entity_id;
ALTER TABLE document_requests
  ADD COLUMN entity_id INT UNSIGNED NULL AFTER client_id,
  ADD COLUMN scope ENUM('company','personal') NOT NULL DEFAULT 'company' AFTER entity_id;
ALTER TABLE messages
  ADD COLUMN entity_id INT UNSIGNED NULL AFTER client_id,
  ADD COLUMN scope ENUM('company','personal') NOT NULL DEFAULT 'company' AFTER entity_id;
ALTER TABLE deadlines
  ADD COLUMN scope ENUM('company','personal') NOT NULL DEFAULT 'company' AFTER entity_id;

UPDATE documents r
JOIN client_entities e ON e.id = (SELECT MIN(e2.id) FROM client_entities e2 WHERE e2.client_id = r.client_id)
SET r.entity_id = e.id, r.scope = e.entity_scope
WHERE r.entity_id IS NULL;
UPDATE document_requests r
JOIN client_entities e ON e.id = (SELECT MIN(e2.id) FROM client_entities e2 WHERE e2.client_id = r.client_id)
SET r.entity_id = e.id, r.scope = e.entity_scope
WHERE r.entity_id IS NULL;
UPDATE messages r
JOIN client_entities e ON e.id = (SELECT MIN(e2.id) FROM client_entities e2 WHERE e2.client_id = r.client_id)
SET r.entity_id = e.id, r.scope = e.entity_scope
WHERE r.entity_id IS NULL;
UPDATE deadlines d JOIN client_entities e ON e.id = d.entity_id SET d.scope = e.entity_scope;

ALTER TABLE documents
  ADD INDEX idx_docs_entity_scope (entity_id, scope),
  ADD CONSTRAINT fk_docs_entity FOREIGN KEY (entity_id) REFERENCES client_entities(id) ON DELETE CASCADE;
ALTER TABLE document_requests
  ADD INDEX idx_reqs_entity_scope (entity_id, scope, status),
  ADD CONSTRAINT fk_reqs_entity FOREIGN KEY (entity_id) REFERENCES client_entities(id) ON DELETE CASCADE;
ALTER TABLE messages
  ADD INDEX idx_messages_entity_scope (entity_id, scope, read_at),
  ADD CONSTRAINT fk_messages_entity FOREIGN KEY (entity_id) REFERENCES client_entities(id) ON DELETE CASCADE;
ALTER TABLE deadlines ADD INDEX idx_deadlines_entity_scope (entity_id, scope, due_date);
