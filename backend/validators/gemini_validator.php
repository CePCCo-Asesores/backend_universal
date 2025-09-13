<?php
/**
 * 📁 backend/validators/gemini_validator.php
 * Validador específico para Gemini API
 */

class GeminiValidator {
    
    private $bannedWords = [
        'spam', 'hack', 'virus', 'malware', 'phishing', 'scam',
        'illegal', 'drugs', 'weapons', 'violence', 'hate',
        'terrorism', 'bomb', 'kill', 'murder', 'suicide'
    ];
    
    private $maxPromptLength = 100000;
    private $minPromptLength = 1;
    private $maxTokens = 30720;
    private $rateLimitPerMinute = 60;
    
    /**
     * Valida los datos de entrada para Gemini API
     */
    public function validate($data) {
        try {
            // Validación de estructura básica
            if (!$this->validateStructure($data)) {
                return false;
            }
            
            // Validación de contenido del prompt
            if (!$this->validatePromptContent($data['prompt'])) {
                return false;
            }
            
            // Validación de seguridad
            if (!$this->validateSecurity($data)) {
                return false;
            }
            
            // Validación de parámetros de generación
            if (!$this->validateGenerationParams($data)) {
                return false;
            }
            
            // Validación de límites y cuotas
            if (!$this->validateLimits($data)) {
                return false;
            }
            
            // Validación de rate limiting
            if (!$this->validateRateLimit()) {
                return false;
            }
            
            error_log("GeminiValidator: Todas las validaciones pasaron para prompt: " . substr($data['prompt'], 0, 50) . "...");
            return true;
            
        } catch (Exception $e) {
            error_log("GeminiValidator error: " . $e->getMessage());
            return false;
        }
    }
    
    private function validateStructure($data) {
        if (!is_array($data)) {
            error_log("GeminiValidator: Data debe ser array");
            return false;
        }
        
        if (!isset($data['prompt'])) {
            error_log("GeminiValidator: Campo 'prompt' requerido");
            return false;
        }
        
        return true;
    }
    
    private function validatePromptContent($prompt) {
        // Verificar tipo
        if (!is_string($prompt)) {
            error_log("GeminiValidator: Prompt debe ser string");
            return false;
        }
        
        // Verificar longitud
        $length = strlen(trim($prompt));
        if ($length < $this->minPromptLength) {
            error_log("GeminiValidator: Prompt muy corto (min: {$this->minPromptLength} chars)");
            return false;
        }
        
        if ($length > $this->maxPromptLength) {
            error_log("GeminiValidator: Prompt muy largo (max: {$this->maxPromptLength} chars)");
            return false;
        }
        
        // Verificar codificación UTF-8
        if (!mb_check_encoding($prompt, 'UTF-8')) {
            error_log("GeminiValidator: Codificación UTF-8 inválida en prompt");
            return false;
        }
        
        // Verificar que no esté completamente vacío o solo espacios
        if (trim($prompt) === '') {
            error_log("GeminiValidator: Prompt no puede estar vacío");
            return false;
        }
        
        // Verificar caracteres de control excesivos
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]{10,}/', $prompt)) {
            error_log("GeminiValidator: Demasiados caracteres de control en prompt");
            return false;
        }
        
        return true;
    }
    
    private function validateSecurity($data) {
        $prompt = strtolower($data['prompt']);
        
        // Verificar palabras prohibidas
        foreach ($this->bannedWords as $bannedWord) {
            if (strpos($prompt, $bannedWord) !== false) {
                error_log("GeminiValidator: Palabra prohibida detectada: $bannedWord");
                return false;
            }
        }
        
        // Verificar intentos de inyección
        if ($this->detectInjectionAttempt($prompt)) {
            error_log("GeminiValidator: Intento de inyección detectado");
            return false;
        }
        
        // Verificar patrones sospechosos
        if ($this->detectSuspiciousPatterns($prompt)) {
            error_log("GeminiValidator: Patrones sospechosos detectados");
            return false;
        }
        
        // Verificar contenido potencialmente dañino
        if ($this->detectHarmfulContent($prompt)) {
            error_log("GeminiValidator: Contenido potencialmente dañino detectado");
            return false;
        }
        
        return true;
    }
    
    private function validateGenerationParams($data) {
        // Validar max_tokens
        if (isset($data['max_tokens'])) {
            if (!is_numeric($data['max_tokens'])) {
                error_log("GeminiValidator: max_tokens debe ser numérico");
                return false;
            }
            
            $maxTokens = (int)$data['max_tokens'];
            if ($maxTokens < 1 || $maxTokens > $this->maxTokens) {
                error_log("GeminiValidator: max_tokens fuera de rango (1-{$this->maxTokens})");
                return false;
            }
        }
        
        // Validar temperature
        if (isset($data['temperature'])) {
            if (!is_numeric($data['temperature'])) {
                error_log("GeminiValidator: temperature debe ser numérico");
                return false;
            }
            
            $temperature = (float)$data['temperature'];
            if ($temperature < 0.0 || $temperature > 2.0) {
                error_log("GeminiValidator: temperature fuera de rango (0.0-2.0)");
                return false;
            }
        }
        
        // Validar top_p
        if (isset($data['top_p'])) {
            if (!is_numeric($data['top_p'])) {
                error_log("GeminiValidator: top_p debe ser numérico");
                return false;
            }
            
            $topP = (float)$data['top_p'];
            if ($topP < 0.0 || $topP > 1.0) {
                error_log("GeminiValidator: top_p fuera de rango (0.0-1.0)");
                return false;
            }
        }
        
        return true;
    }
    
    private function validateLimits($data) {
        // Estimar tokens del prompt (aproximado)
        $promptTokens = str_word_count($data['prompt']) * 1.3; // Factor aproximado
        $maxTokens = $data['max_tokens'] ?? 1000;
        
        // Verificar límite total de tokens por request
        if (($promptTokens + $maxTokens) > $this->maxTokens) {
            error_log("GeminiValidator: Total de tokens excede límite máximo");
            return false;
        }
        
        return true;
    }
    
    private function validateRateLimit() {
        // Implementación básica de rate limiting
        // En producción, usar Redis o base de datos
        $cacheFile = sys_get_temp_dir() . '/gemini_rate_limit.txt';
        $currentTime = time();
        
        if (file_exists($cacheFile)) {
            $lastRequestTime = (int)file_get_contents($cacheFile);
            if (($currentTime - $lastRequestTime) < 1) { // Max 1 request per second
                error_log("GeminiValidator: Rate limit excedido");
                return false;
            }
        }
        
        file_put_contents($cacheFile, $currentTime);
        return true;
    }
    
    private function detectInjectionAttempt($prompt) {
        $injectionPatterns = [
            '/\<script.*?\>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/\bexec\s*\(/i',
            '/\beval\s*\(/i',
            '/\$\w+\s*=/i',
            '/\{\{.*?\}\}/i', // Template injection
            '/\$\{.*?\}/i'    // Expression injection
        ];
        
        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $prompt)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function detectSuspiciousPatterns($prompt) {
        // Detectar repeticiones excesivas
        if (preg_match('/(.{10,})\1{10,}/', $prompt)) {
            error_log("GeminiValidator: Repetición excesiva detectada");
            return true;
        }
        
        // Detectar demasiados caracteres especiales consecutivos
        if (preg_match('/[!@#$%^&*()]{20,}/', $prompt)) {
            error_log("GeminiValidator: Demasiados caracteres especiales");
            return true;
        }
        
        // Detectar URLs sospechosas en exceso
        if (preg_match_all('/https?:\/\/[^\s]+/i', $prompt) > 10) {
            error_log("GeminiValidator: Demasiadas URLs en prompt");
            return true;
        }
        
        return false;
    }
    
    private function detectHarmfulContent($prompt) {
        $harmfulPatterns = [
            '/how to (make|create|build).*(bomb|explosive|weapon)/i',
            '/step.*by.*step.*(suicide|self.*harm)/i',
            '/(hack|crack|break.*into).*(system|account|password)/i',
            '/(generate|create).*(fake|forged).*(document|id|certificate)/i'
        ];
        
        foreach ($harmfulPatterns as $pattern) {
            if (preg_match($pattern, $prompt)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Limpia y sanitiza el prompt
     */
    public function sanitizePrompt($prompt) {
        // Remover espacios extra
        $prompt = trim($prompt);
        $prompt = preg_replace('/\s+/', ' ', $prompt);
        
        // Remover caracteres de control
        $prompt = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $prompt);
        
        // Normalizar saltos de línea
        $prompt = preg_replace('/\r\n|\r/', "\n", $prompt);
        
        // Limitar saltos de línea consecutivos
        $prompt = preg_replace('/\n{3,}/', "\n\n", $prompt);
        
        return $prompt;
    }
    
    /**
     * Retorna estadísticas de validación
     */
    public function getValidationStats() {
        return [
            'max_prompt_length' => $this->maxPromptLength,
            'min_prompt_length' => $this->minPromptLength,
            'max_tokens' => $this->maxTokens,
            'banned_words_count' => count($this->bannedWords),
            'rate_limit_per_minute' => $this->rateLimitPerMinute,
            'validator_version' => '1.0.0'
        ];
    }
}
