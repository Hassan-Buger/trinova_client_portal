-- Apply once to an existing TriNova database before deploying this feature.
ALTER TABLE client_entities
  ADD COLUMN entity_type VARCHAR(80) NOT NULL DEFAULT 'Other' AFTER company_name,
  ADD COLUMN attributes JSON NULL AFTER tax_reference;

ALTER TABLE deadlines
  ADD COLUMN entity_id INT UNSIGNED NULL AFTER client_id,
  MODIFY COLUMN type VARCHAR(100) NOT NULL;

-- Preserve legacy client-wide deadlines by assigning them to the client's first entity.
INSERT INTO client_entities (client_id, company_name, entity_type, attributes)
SELECT c.id, CONCAT(u.name, ' - General'), 'Other', JSON_OBJECT()
FROM clients c JOIN users u ON u.id=c.user_id
WHERE NOT EXISTS (SELECT 1 FROM client_entities e WHERE e.client_id=c.id);

UPDATE deadlines d
SET d.entity_id=(SELECT MIN(e.id) FROM client_entities e WHERE e.client_id=d.client_id)
WHERE d.entity_id IS NULL;

ALTER TABLE deadlines
  MODIFY COLUMN entity_id INT UNSIGNED NOT NULL,
  ADD CONSTRAINT fk_deadlines_entity FOREIGN KEY (entity_id) REFERENCES client_entities(id) ON DELETE CASCADE,
  ADD INDEX idx_deadlines_entity_date (entity_id,due_date);
