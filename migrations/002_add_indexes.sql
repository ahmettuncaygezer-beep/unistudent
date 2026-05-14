-- 002_add_indexes.sql
-- Performans indeksleri. ensureAllTables() bunları idempotent çalıştırır.

CREATE INDEX idx_tx_user_date    ON transactions(user_id, transaction_date);
CREATE INDEX idx_tx_user_type    ON transactions(user_id, type);
CREATE INDEX idx_budget_user_ym  ON user_budgets(user_id, year, month);
CREATE INDEX idx_sub_user_active ON subscriptions(user_id, is_active);
CREATE INDEX idx_sub_next_bill   ON subscriptions(next_billing, is_active);
CREATE INDEX idx_notif_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_blogs_status    ON blogs(status, updated_at);
