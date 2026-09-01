-- Phase 11: centralized, persistent notification metadata.
ALTER TABLE notifications
  ADD COLUMN IF NOT EXISTS module VARCHAR(50) NOT NULL DEFAULT 'system' AFTER link,
  ADD COLUMN IF NOT EXISTS related_id INT NULL AFTER module,
  ADD INDEX IF NOT EXISTS idx_notifications_recipient_read (user_id,is_read,id);
