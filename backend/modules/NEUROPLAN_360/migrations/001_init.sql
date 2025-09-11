-- Crea schema y tablas base del módulo
CREATE SCHEMA IF NOT EXISTS NEUROPLAN_360;

CREATE TABLE IF NOT EXISTS NEUROPLAN_360.sessions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email TEXT NOT NULL,
  step INTEGER NOT NULL DEFAULT 1,
  data JSONB NOT NULL DEFAULT '{}'::jsonb,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS NEUROPLAN_360.plans (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email TEXT NOT NULL,
  input JSONB NOT NULL,
  plan  JSONB NOT NULL,
  status TEXT NOT NULL DEFAULT 'generated',
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
