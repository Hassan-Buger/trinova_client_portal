-- Migration script: Delete Functionality (Single & Bulk) Soft Delete Support

ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE client_entities ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE entity_directors ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE document_requests ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE messages ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE deadlines ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE meetings ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;
ALTER TABLE client_csv_imports ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

-- Indexes for performance filtering active records
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_deleted_at (deleted_at);
ALTER TABLE clients ADD INDEX IF NOT EXISTS idx_clients_deleted_at (deleted_at);
ALTER TABLE client_entities ADD INDEX IF NOT EXISTS idx_entities_deleted_at (deleted_at);
ALTER TABLE documents ADD INDEX IF NOT EXISTS idx_docs_deleted_at (deleted_at);
ALTER TABLE document_requests ADD INDEX IF NOT EXISTS idx_reqs_deleted_at (deleted_at);
ALTER TABLE messages ADD INDEX IF NOT EXISTS idx_msg_deleted_at (deleted_at);
ALTER TABLE deadlines ADD INDEX IF NOT EXISTS idx_deadlines_deleted_at (deleted_at);
ALTER TABLE meetings ADD INDEX IF NOT EXISTS idx_meetings_deleted_at (deleted_at);
ALTER TABLE client_csv_imports ADD INDEX IF NOT EXISTS idx_csv_deleted_at (deleted_at);
