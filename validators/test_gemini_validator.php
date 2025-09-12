<?php
/**
 * 📁 validators/test_gemini_validator.php
 * Validador específico para el módulo test_gemini
 */

class TestGeminiValidator {
    
    private $bannedWords = ['spam', 'hack', 'virus', 'malware'];
    private $maxPromptLength = 5000;
    private $minPromptLength = 3;
    
    /**
     * Valida los datos de entrada específicos para Gemini
     */
    public function validate($data) {
        try {
            // Validación básica de estructura
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
            
            // Validación de límites
            if (!$this->validateLimits($data)) {
                return false;
            }
            
            error_log("TestGeminiValidator: All validations passed for prompt: " . substr($data['prompt'], 0, 50) . "...");
            return true;
            
        } catch (Exception $e) {
            error_log("TestGeminiValidator error: " . $e->getMessage());
            return false;
        }
    }
    
    private function validateStructure($data) {
        if (!is_array($data)) {
            error_log("Validator: Data must be array");
            return false;
        }
        
        if (!isset($data['prompt'])) {
            error_log("Validator: Missing 'prompt' field");
            return false;
        }
        
        return true;
    }
    
    private function validatePromptContent($prompt) {
        // Verificar tipo
        if (!is_string($prompt)) {
            error_log("Validator: Prompt must be string");
            return false;
        }
        
        // Verificar longitud
        $length = strlen(trim($prompt));
        if ($length < $this->minPromptLength) {
            error_log("Validator: Prompt too short (min: {$this->minPromptLength} chars)");
            return false;
        }
        
        if ($length > $this->maxPromptLength) {
            error_log("Validator: Prompt too long (max: {$this->maxPromptLength} chars)");
            return false;
        }
        
        // Verificar caracteres válidos (UTF-8)
        if (!mb_check_encoding($prompt, 'UTF-8')) {
            error_log("Validator: Invalid UTF-8 encoding in prompt");
            return false;
        }
        
        return true;
    }
    
    private function validateSecurity($data) {
        $prompt = strtolower($data['prompt']);
        
        // Verificar palabras prohibidas
        foreach ($this->bannedWords as $bannedWord) {
            if (strpos($prompt, $bannedWord) !== false) {
                error_log("Validator: Banned word detected: $bannedWord");
                return false;
            }
        }
        
        // Verificar intentos de inyección
        if ($this->detectInjectionAttempt($prompt)) {
            error_log("Validator: Potential injection attempt detected");
            return false;
        }
        
        // Verificar patrones sospechosos
        if ($this->detectSuspiciousPatterns($prompt)) {
            error_log("Validator: Suspicious patterns detected");
            return false;
        }
        
        return true;
    }
    
    private function validateLimits($data) {
        // Validar max_tokens si está presente
        if (isset($data['max_tokens'])) {
            if (!is_numeric($data['max_tokens'])) {
                error_log("Validator: max_tokens must be numeric");
                return false;
            }
            
            $maxTokens = (int)$data['max_tokens'];
            if ($maxTokens < 1 || $maxTokens > 1000) {
                error_log("Validator: max_tokens out of range (1-1000)");
                return false;
            }
        }
        
        return true;
    }
    
    private function detectInjectionAttempt($prompt) {
        $injectionPatterns = [
            '/\<script.*?\>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/\bexec\b/i',
            '/\beval\b/i',
            '/\$\w+\s*=/i'
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
        if (preg_match('/(.{10,})\1{5,}/', $prompt)) {
            error_log("Validator: Excessive repetition detected");
            return true;
        }
        
        // Detectar demasiados caracteres especiales consecutivos
        if (preg_match('/[!@#$%^&*()]{10,}/', $prompt)) {
            error_log("Validator: Too many special characters");
            return true;
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
        $prompt = preg_replace('/[\x00-\x1F\x7F]/', '', $prompt);
        
        // Escapar caracteres HTML
        $prompt = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');
        
        return $prompt;
    }
    
    /**
     * Retorna estadísticas de validación
     */
    public function getValidationStats() {
        return [
            'max_prompt_length' => $this->maxPromptLength,
            'min_prompt_length' => $this->minPromptLength,
            'banned_words_count' => count($this->bannedWords),
            'validator_version' => '1.0.0'
        ];
    }
}
