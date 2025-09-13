# backend/routes.yaml - COMPATIBLE con tu index.php
# Sistema completo con autenticación usando tu arquitectura existente

# =================
# AUTENTICACIÓN (usando tu Controllers\AuthController)
# =================
"/auth/google":
  controller: "Controllers\\AuthController"
  action: "loginConGoogle"

"/auth/google/callback":
  controller: "Controllers\\AuthController"
  action: "googleCallback"

"/auth/logout":
  controller: "Controllers\\AuthController"
  action: "logout"

"/auth/me":
  controller: "Controllers\\AuthController"
  action: "me"

"/auth/status":
  controller: "Controllers\\AuthController"
  action: "status"

"/auth/usage":
  controller: "Controllers\\AuthController"
  action: "usage"

# =================
# MÓDULOS EXISTENTES
# =================
"/module/activate":
  controller: "ModuleController"
  action: "activate"

"/module/registerUser":
  controller: "ModuleController"
  action: "registerUser"

# =================
# HEALTH & METRICS (públicas)
# =================
"/health":
  controller: "HealthController"
  action: "ping"

"/health/database":
  controller: "HealthController"
  action: "databaseHealth"

"/metrics":
  controller: "MetricsController"
  action: "index"

# =================
# GEMINI (con autenticación opcional)
# =================
# Módulo de prueba Gemini - FORMATO CORRECTO
"/test-gemini/health":
  controller: "TestGeminiController"
  action: "healthCheck"

"/test-gemini/process":
  controller: "TestGeminiController"
  action: "handleRequest"
  authenticated: true

# Módulo Gemini Real
"/gemini/generate":
  controller: "GeminiController"
  action: "generateText"
  authenticated: true

"/gemini/health":
  controller: "GeminiController"
  action: "healthCheck"

"/gemini/models":
  controller: "GeminiController"
  action: "listModels"
  authenticated: true

# =================
# ASISTENTE UNIVERSAL (con autenticación)
# =================
"/assistant/execute":
  controller: "AssistantController"
  action: "execute"
  authenticated: true

"/assistant/list":
  controller: "AssistantController"
  action: "listAssistants"
  authenticated: true

"/assistant/ui-config":
  controller: "AssistantController"
  action: "getUIConfig"
  authenticated: true

"/assistant/health":
  controller: "AssistantController"
  action: "healthCheck"

# =================
# GESTIÓN DE USUARIOS
# =================
"/user/api-keys":
  controller: "UserController"
  action: "listApiKeys"
  authenticated: true

"/user/api-keys/add":
  controller: "UserController"
  action: "addApiKey"
  authenticated: true

"/user/profile":
  controller: "UserController"
  action: "getProfile"
  authenticated: true

# =================
# RUTAS TEMPORALES DE SETUP (eliminar después de confirmar que funciona)
# =================
"/setup/database":
  controller: "SetupController"
  action: "initDatabase"

"/setup/health":
  controller: "SetupController"
  action: "health"

"/setup/phpinfo":
  controller: "PhpInfoController"
  action: "info"
