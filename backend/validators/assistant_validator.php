<?php
/**
 * 📁 backend/validators/assistant_validator.php
 * Validador universal para todos los asistentes
 */

class AssistantValidator {
    
    private $maxStringLength = 50000;
    private $maxArrayDepth = 10;
    private $bannedWords = [
        'hack', 'exploit', 'malware', 'virus', 'attack', 'breach',
        'illegal', 'fraud', 'scam', 'phishing', 'spam'
    ];
    private $rateLimitPerMinute = 30;
    
    /**
     * Valida los datos de entrada para cualquier asistente
     */
    public function validate($data) {
        try {
            // Validación de estructura básica
            if (!$this->validateStructure($data)) {
                return false;
            }
            
            // Validación de assistant_id
            if (!$this->validateAssistantId($data['assistant_id'])) {
                return false;
            }
            
            // Validación de seguridad general
            if (!$this->validateSecurity($data)) {
                return false;
            }
            
            // Validación de límites y tamaños
            if (!$this->validateLimits($data)) {
                return false;
            }
            
            // Validación de rate limiting
            if (!$this->validateRateLimit()) {
                return false;
            }
            
            // Validación específica por asistente
            if (!$this->validateByAssistant($data)) {
                return false;
            }
            
            error_log("AssistantValidator: Validación exitosa para asistente: " . $data['assistant_id']);
            return true;
            
        } catch (Exception $e) {
            error_log("AssistantValidator error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valida estructura básica de datos
     */
    private function validateStructure($data) {
        if (!is_array($data)) {
            error_log("AssistantValidator: Data debe ser array");
            return false;
        }
        
        if (!isset($data['assistant_id'])) {
            error_log("AssistantValidator: Campo 'assistant_id' requerido");
            return false;
        }
        
        // Verificar profundidad del array
        if ($this->getArrayDepth($data) > $this->maxArrayDepth) {
            error_log("AssistantValidator: Array demasiado profundo (max: {$this->maxArrayDepth})");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida el ID del asistente
     */
    private function validateAssistantId($assistantId) {
        if (!is_string($assistantId)) {
            error_log("AssistantValidator: assistant_id debe ser string");
            return false;
        }
        
        if (strlen($assistantId) < 3 || strlen($assistantId) > 50) {
            error_log("AssistantValidator: assistant_id debe tener entre 3 y 50 caracteres");
            return false;
        }
        
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $assistantId)) {
            error_log("AssistantValidator: assistant_id formato inválido (debe empezar con letra minúscula)");
            return false;
        }
        
        // Lista de IDs reservados
        $reservedIds = ['admin', 'system', 'root', 'api', 'health', 'config'];
        if (in_array($assistantId, $reservedIds)) {
            error_log("AssistantValidator: assistant_id '{$assistantId}' está reservado");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validación de seguridad general
     */
    private function validateSecurity($data) {
        // Verificar palabras prohibidas en todos los strings
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                if (!$this->validateStringContent($value)) {
                    error_log("AssistantValidator: Contenido sospechoso en campo '{$key}'");
                    return false;
                }
            } elseif (is_array($value)) {
                if (!$this->validateArraySecurity($value)) {
                    error_log("AssistantValidator: Contenido sospechoso en array '{$key}'");
                    return false;
                }
            }
        }
        
        // Verificar intentos de inyección
        if ($this->detectInjectionAttempt($data)) {
            error_log("AssistantValidator: Intento de inyección detectado");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida contenido de string
     */
    private function validateStringContent($string) {
        // Verificar longitud
        if (strlen($string) > $this->maxStringLength) {
            error_log("AssistantValidator: String excede longitud máxima ({$this->maxStringLength})");
            return false;
        }
        
        // Verificar palabras prohibidas
        $lowerString = strtolower($string);
        foreach ($this->bannedWords as $bannedWord) {
            if (strpos($lowerString, $bannedWord) !== false) {
                error_log("AssistantValidator: Palabra prohibida detectada: {$bannedWord}");
                return false;
            }
        }
        
        // Verificar codificación UTF-8
        if (!mb_check_encoding($string, 'UTF-8')) {
            error_log("AssistantValidator: Codificación UTF-8 inválida");
            return false;
        }
        
        // Verificar caracteres de control excesivos
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]{20,}/', $string)) {
            error_log("AssistantValidator: Demasiados caracteres de control");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida arrays recursivamente para seguridad
     */
    private function validateArraySecurity($array) {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                if (!$this->validateStringContent($value)) {
                    return false;
                }
            } elseif (is_array($value)) {
                if (!$this->validateArraySecurity($value)) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Detecta intentos de inyección
     */
    private function detectInjectionAttempt($data) {
        $dataString = json_encode($data);
        
        $injectionPatterns = [
            '/\<script.*?\>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/\bexec\s*\(/i',
            '/\beval\s*\(/i',
            '/\$\w+\s*=/i',
            '/\{\{.*?\}\}/i',
            '/\$\{.*?\}/i',
            '/<\?php/i',
            '/<%.*?%>/i'
        ];
        
        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $dataString)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Valida límites y tamaños
     */
    private function validateLimits($data) {
        // Verificar tamaño total de datos
        $dataSize = strlen(json_encode($data));
        $maxDataSize = 1024 * 1024; // 1MB
        
        if ($dataSize > $maxDataSize) {
            error_log("AssistantValidator: Datos exceden tamaño máximo (1MB)");
            return false;
        }
        
        // Verificar número de campos
        $fieldCount = $this->countFields($data);
        if ($fieldCount > 100) {
            error_log("AssistantValidator: Demasiados campos ({$fieldCount} > 100)");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida rate limiting básico
     */
    private function validateRateLimit() {
        // Implementación básica usando archivo temporal
        $rateLimitFile = sys_get_temp_dir() . '/assistant_rate_limit.json';
        $currentTime = time();
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $rateLimitData = [];
        if (file_exists($rateLimitFile)) {
            $content = file_get_contents($rateLimitFile);
            $rateLimitData = json_decode($content, true) ?: [];
        }
        
        // Limpiar datos antiguos (más de 1 minuto)
        foreach ($rateLimitData as $ip => $data) {
            if (($currentTime - $data['first_request']) > 60) {
                unset($rateLimitData[$ip]);
            }
        }
        
        // Verificar límite para IP actual
        if (!isset($rateLimitData[$clientIp])) {
            $rateLimitData[$clientIp] = [
                'count' => 1,
                'first_request' => $currentTime
            ];
        } else {
            $rateLimitData[$clientIp]['count']++;
            
            if ($rateLimitData[$clientIp]['count'] > $this->rateLimitPerMinute) {
                error_log("AssistantValidator: Rate limit excedido para IP: {$clientIp}");
                return false;
            }
        }
        
        // Guardar datos actualizados
        file_put_contents($rateLimitFile, json_encode($rateLimitData));
        
        return true;
    }
    
    /**
     * Validación específica por asistente
     */
    private function validateByAssistant($data) {
        $assistantId = $data['assistant_id'];
        
        switch ($assistantId) {
            case 'prompt_architect':
                return $this->validatePromptArchitect($data);
                
            case 'backend_generator':
                return $this->validateBackendGenerator($data);
                
            case 'business_analyst':
                return $this->validateBusinessAnalyst($data);
                
            default:
                // Validación genérica para asistentes no específicos
                return $this->validateGenericAssistant($data);
        }
    }
    
    /**
     * Validación específica para Prompt Architect
     */
    private function validatePromptArchitect($data) {
        // Verificar campos requeridos
        if (!isset($data['idea']) || trim($data['idea']) === '') {
            error_log("AssistantValidator: Prompt Architect requiere campo 'idea'");
            return false;
        }
        
        // Verificar longitud mínima de idea
        if (strlen($data['idea']) < 10) {
            error_log("AssistantValidator: 'idea' muy corta (min: 10 caracteres)");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validación específica para Backend Generator
     */
    private function validateBackendGenerator($data) {
        // Verificar campos requeridos
        if (!isset($data['module_name']) || trim($data['module_name']) === '') {
            error_log("AssistantValidator: Backend Generator requiere 'module_name'");
            return false;
        }
        
        // Verificar formato de module_name
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $data['module_name'])) {
            error_log("AssistantValidator: 'module_name' formato inválido");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validación específica para Business Analyst
     */
    private function validateBusinessAnalyst($data) {
        // Verificar campos requeridos
        if (!isset($data['business_idea']) || trim($data['business_idea']) === '') {
            error_log("AssistantValidator: Business Analyst requiere 'business_idea'");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validación genérica para asistentes no específicos
     */
    private function validateGenericAssistant($data) {
        // Al menos debe tener algún campo de contenido
        $contentFields = ['prompt', 'content', 'input', 'text', 'idea'];
        $hasContent = false;
        
        foreach ($contentFields as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && trim($data[$field]) !== '') {
                $hasContent = true;
                break;
            }
        }
        
        if (!$hasContent) {
            error_log("AssistantValidator: Asistente requiere al menos un campo de contenido");
            return false;
        }
        
        return true;
    }
    
    /**
     * Calcula profundidad de array
     */
    private function getArrayDepth($array) {
        $maxDepth = 1;
        
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = 1 + $this->getArrayDepth($value);
                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
        }
        
        return $maxDepth;
    }
    
    /**
     * Cuenta campos totales en estructura
     */
    private function countFields($data) {
        $count = 0;
        
        foreach ($data as $value) {
            $count++;
            if (is_array($value)) {
                $count += $this->countFields($value);
            }
        }
        
        return $count;
    }
    
    /**
     * Sanitiza datos de entrada
     */
    public function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        if (is_string($data)) {
            // Remover espacios extra
            $data = trim($data);
            $data = preg_replace('/\s+/', ' ', $data);
            
            // Remover caracteres de control
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            
            // Normalizar saltos de línea
            $data = preg_replace('/\r\n|\r/', "\n", $data);
            
            return $data;
        }
        
        return $data;
    }
    
    /**
     * Retorna estadísticas de validación
     */
    public function getValidationStats() {
        return [
            'max_string_length' => $this->maxStringLength,
            'max_array_depth' => $this->maxArrayDepth,
            'banned_words_count' => count($this->bannedWords),
            'rate_limit_per_minute' => $this->rateLimitPerMinute,
            'validator_version' => '1.0.0'
        ];
    }
}
