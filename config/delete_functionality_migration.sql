-- Migration script: Delete Functionality (Single & Bulk) Soft Delete Support
-- Production-safe & compatible with both MySQL 8.0+ and MariaDB

SET @db = DATABASE();

-- users.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='users' AND column_name='deleted_at')=0, 'ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='users' AND index_name='idx_users_deleted_at')=0, 'ALTER TABLE users ADD INDEX idx_users_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- clients.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='clients' AND column_name='deleted_at')=0, 'ALTER TABLE clients ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='clients' AND index_name='idx_clients_deleted_at')=0, 'ALTER TABLE clients ADD INDEX idx_clients_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- client_entities.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='client_entities' AND column_name='deleted_at')=0, 'ALTER TABLE client_entities ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='client_entities' AND index_name='idx_entities_deleted_at')=0, 'ALTER TABLE client_entities ADD INDEX idx_entities_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- entity_directors.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=@db AND table_name='entity_directors')>0 AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='entity_directors' AND column_name='deleted_at')=0, 'ALTER TABLE entity_directors ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=@db AND table_name='entity_directors')>0 AND (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='entity_directors' AND index_name='idx_entity_directors_deleted_at')=0, 'ALTER TABLE entity_directors ADD INDEX idx_entity_directors_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- documents.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='documents' AND column_name='deleted_at')=0, 'ALTER TABLE documents ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='documents' AND index_name='idx_docs_deleted_at')=0, 'ALTER TABLE documents ADD INDEX idx_docs_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- document_requests.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='document_requests' AND column_name='deleted_at')=0, 'ALTER TABLE document_requests ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='document_requests' AND index_name='idx_reqs_deleted_at')=0, 'ALTER TABLE document_requests ADD INDEX idx_reqs_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- messages.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='messages' AND column_name='deleted_at')=0, 'ALTER TABLE messages ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='messages' AND index_name='idx_msg_deleted_at')=0, 'ALTER TABLE messages ADD INDEX idx_msg_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- deadlines.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='deadlines' AND column_name='deleted_at')=0, 'ALTER TABLE deadlines ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='deadlines' AND index_name='idx_deadlines_deleted_at')=0, 'ALTER TABLE deadlines ADD INDEX idx_deadlines_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- meetings.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='meetings' AND column_name='deleted_at')=0, 'ALTER TABLE meetings ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='meetings' AND index_name='idx_meetings_deleted_at')=0, 'ALTER TABLE meetings ADD INDEX idx_meetings_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- client_csv_imports.deleted_at
SET @sql = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=@db AND table_name='client_csv_imports')>0 AND (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=@db AND table_name='client_csv_imports' AND column_name='deleted_at')=0, 'ALTER TABLE client_csv_imports ADD COLUMN deleted_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=@db AND table_name='client_csv_imports')>0 AND (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=@db AND table_name='client_csv_imports' AND index_name='idx_csv_deleted_at')=0, 'ALTER TABLE client_csv_imports ADD INDEX idx_csv_deleted_at (deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

