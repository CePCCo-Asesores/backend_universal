<?php
/**
 * 📁 backend/contracts/gemini_contract.php
 * Contrato para el módulo real de Gemini API
 */

class GeminiContract {
    
    /**
     * Valida los datos de entrada para Gemini API
     * 
     * Precondiciones:
     * - 'prompt' es requerido y debe ser string no vacío
     * - 'max_tokens' opcional, entero entre 1 y 30720
     * - 'temperature' opcional, float entre 0.0 y 2.0
     */
    public function validateInput($data) {
        // Verificar que es array
        if (!is_array($data)) {
            error_log("GeminiContract: Input debe ser array");
            return false;
        }
        
        // Verificar prompt requerido
        if (!isset($data['prompt']) || !is_string($data['prompt'])) {
            error_log("GeminiContract: 'prompt' es requerido y debe ser string");
            return false;
        }
        
        // Verificar prompt no vacío
        if (trim($data['prompt']) === '') {
            error_log("GeminiContract: 'prompt' no puede estar vacío");
            return false;
        }
        
        // Verificar longitud del prompt
        if (strlen($data['prompt']) > 100000) {
            error_log("GeminiContract: 'prompt' no puede exceder 100,000 caracteres");
            return false;
        }
        
        // Verificar max_tokens si existe
        if (isset($data['max_tokens'])) {
            if (!is_int($data['max_tokens']) || $data['max_tokens'] < 1 || $data['max_tokens'] > 30720) {
                error_log("GeminiContract: 'max_tokens' debe ser entero entre 1 y 30720");
                return false;
            }
        }
        
        // Verificar temperature si existe
        if (isset($data['temperature'])) {
            if (!is_numeric($data['temperature']) || $data['temperature'] < 0.0 || $data['temperature'] > 2.0) {
                error_log("GeminiContract: 'temperature' debe ser número entre 0.0 y 2.0");
                return false;
            }
        }
        
        // Verificar top_p si existe
        if (isset($data['top_p'])) {
            if (!is_numeric($data['top_p']) || $data['top_p'] < 0.0 || $data['top_p'] > 1.0) {
                error_log("GeminiContract: 'top_p' debe ser número entre 0.0 y 1.0");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida los datos de salida de Gemini API
     * 
     * Postcondiciones:
     * - Debe contener 'prompt', 'response', 'usage', 'model', 'temperature', 'processing_time'
     * - 'response' debe ser string no vacío
     * - 'usage' debe tener estructura válida de tokens
     */
    public function validateOutput($data) {
        // Verificar que es array
        if (!is_array($data)) {
            error_log("GeminiContract: Output debe ser array");
            return false;
        }
        
        // Verificar campos requeridos
        $requiredFields = ['prompt', 'response', 'usage', 'model', 'temperature', 'processing_time'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                error_log("GeminiContract: Campo requerido '{$field}' faltante en output");
                return false;
            }
        }
        
        // Verificar response no vacío
        if (!is_string($data['response']) || trim($data['response']) === '') {
            error_log("GeminiContract: 'response' debe ser string no vacío");
            return false;
        }
        
        // Verificar estructura de usage
        if (!is_array($data['usage'])) {
            error_log("GeminiContract: 'usage' debe ser array");
            return false;
        }
        
        $usageFields = ['prompt_tokens', 'completion_tokens', 'total_tokens'];
        foreach ($usageFields as $field) {
            if (!isset($data['usage'][$field]) || !is_int($data['usage'][$field]) || $data['usage'][$field] < 0) {
                error_log("GeminiContract: 'usage.{$field}' debe ser entero positivo");
                return false;
            }
        }
        
        // Verificar consistencia de tokens
        $expectedTotal = $data['usage']['prompt_tokens'] + $data['usage']['completion_tokens'];
        if ($data['usage']['total_tokens'] !== $expectedTotal) {
            error_log("GeminiContract: Inconsistencia en conteo de tokens");
            return false;
        }
        
        // Verificar model
        if (!is_string($data['model']) || empty($data['model'])) {
            error_log("GeminiContract: 'model' debe ser string no vacío");
            return false;
        }
        
        // Verificar temperature
        if (!is_numeric($data['temperature'])) {
            error_log("GeminiContract: 'temperature' debe ser numérico");
            return false;
        }
        
        // Verificar processing_time formato
        if (!is_string($data['processing_time']) || !preg_match('/^\d+ms$/', $data['processing_time'])) {
            error_log("GeminiContract: 'processing_time' debe estar en formato 'NNNms'");
            return false;
        }
        
        // Verificar longitud razonable de respuesta
        if (strlen($data['response']) > 100000) {
            error_log("GeminiContract: 'response' excede longitud máxima permitida");
            return false;
        }
        
        return true;
    }
    
    /**
     * Define las invariantes del módulo Gemini
     */
    public function checkInvariants() {
        // Verificar que las clases necesarias existen
        if (!class_exists('GeminiController')) {
            error_log("GeminiContract: GeminiController class no encontrada");
            return false;
        }
        
        if (!class_exists('GeminiValidator')) {
            error_log("GeminiContract: GeminiValidator class no encontrada");
            return false;
        }
        
        // Verificar que cURL está disponible
        if (!function_exists('curl_init')) {
            error_log("GeminiContract: cURL extension no disponible");
            return false;
        }
        
        // Verificar que JSON está disponible
        if (!function_exists('json_encode') || !function_exists('json_decode')) {
            error_log("GeminiContract: JSON functions no disponibles");
            return false;
        }
        
        return true;
    }
    
    /**
     * Retorna la documentación del contrato
     */
    public function getContractDocumentation() {
        return [
            'module' => 'gemini',
            'version' => '1.0.0',
            'description' => 'Integración real con Google Gemini API',
            'api_version' => 'v1beta',
            'model' => 'gemini-pro',
            'input_contract' => [
                'prompt' => 'string (required, 1-100000 chars)',
                'max_tokens' => 'integer (optional, 1-30720)',
                'temperature' => 'float (optional, 0.0-2.0)',
                'top_p' => 'float (optional, 0.0-1.0)'
            ],
            'output_contract' => [
                'prompt' => 'string (echoed input)',
                'response' => 'string (AI generated text)',
                'usage' => [
                    'prompt_tokens' => 'integer (positive)',
                    'completion_tokens' => 'integer (positive)',
                    'total_tokens' => 'integer (sum of above)'
                ],
                'model' => 'string (model used)',
                'temperature' => 'float (temperature used)',
                'processing_time' => 'string (format: NNNms)'
            ],
            'invariants' => [
                'GeminiController class must exist',
                'GeminiValidator class must exist',
                'cURL extension must be available',
                'JSON functions must be available',
                'All inputs must pass validation',
                'All outputs must conform to contract'
            ],
            'environment_variables' => [
                'GEMINI_API_KEY' => 'required - API key from Google AI Studio'
            ]
        ];
    }
}
