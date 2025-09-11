CREATE INDEX IF NOT EXISTS idx_np360_sessions_email ON NEUROPLAN_360.sessions(email);
CREATE INDEX IF NOT EXISTS idx_np360_plans_email    ON NEUROPLAN_360.plans(email);
CREATE INDEX IF NOT EXISTS idx_np360_plans_created  ON NEUROPLAN_360.plans(created_at);
