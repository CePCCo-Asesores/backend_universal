<?php
/**
 * 📁 contracts/test_gemini_contract.php
 * Contrato que define las reglas del módulo test_gemini
 */

class TestGeminiContract {
    
    /**
     * Valida los datos de entrada según el contrato
     * 
     * Precondiciones:
     * - Los datos deben ser un array
     * - Debe contener 'prompt' como string no vacío
     * - 'max_tokens' debe ser entero entre 1 y 1000 (opcional)
     */
    public function validateInput($data) {
        // Verificar que es array
        if (!is_array($data)) {
            error_log("Contract violation: Input must be array");
            return false;
        }
        
        // Verificar prompt requerido
        if (!isset($data['prompt']) || !is_string($data['prompt'])) {
            error_log("Contract violation: 'prompt' is required and must be string");
            return false;
        }
        
        // Verificar prompt no vacío
        if (trim($data['prompt']) === '') {
            error_log("Contract violation: 'prompt' cannot be empty");
            return false;
        }
        
        // Verificar max_tokens si existe
        if (isset($data['max_tokens'])) {
            if (!is_int($data['max_tokens']) || $data['max_tokens'] < 1 || $data['max_tokens'] > 1000) {
                error_log("Contract violation: 'max_tokens' must be integer between 1 and 1000");
                return false;
            }
        }
        
        // Verificar longitud del prompt
        if (strlen($data['prompt']) > 5000) {
            error_log("Contract violation: 'prompt' cannot exceed 5000 characters");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida los datos de salida según el contrato
     * 
     * Postcondiciones:
     * - Debe retornar array con 'prompt', 'response', 'tokens_used', 'processing_time'
     * - 'response' debe ser string no vacío
     * - 'tokens_used' debe ser entero positivo
     */
    public function validateOutput($data) {
        // Verificar que es array
        if (!is_array($data)) {
            error_log("Contract violation: Output must be array");
            return false;
        }
        
        // Verificar campos requeridos
        $requiredFields = ['prompt', 'response', 'tokens_used', 'processing_time'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                error_log("Contract violation: Missing required field '$field' in output");
                return false;
            }
        }
        
        // Verificar response no vacío
        if (!is_string($data['response']) || trim($data['response']) === '') {
            error_log("Contract violation: 'response' must be non-empty string");
            return false;
        }
        
        // Verificar tokens_used es entero positivo
        if (!is_int($data['tokens_used']) || $data['tokens_used'] <= 0) {
            error_log("Contract violation: 'tokens_used' must be positive integer");
            return false;
        }
        
        // Verificar processing_time formato
        if (!is_string($data['processing_time']) || !preg_match('/^\d+ms$/', $data['processing_time'])) {
            error_log("Contract violation: 'processing_time' must be in format 'NNNms'");
            return false;
        }
        
        return true;
    }
    
    /**
     * Define las invariantes del módulo
     * Reglas que siempre deben cumplirse
     */
    public function checkInvariants() {
        // Verificar que las clases necesarias existen
        if (!class_exists('TestGeminiController')) {
            error_log("Invariant violation: TestGeminiController class not found");
            return false;
        }
        
        if (!class_exists('TestGeminiValidator')) {
            error_log("Invariant violation: TestGeminiValidator class not found");
            return false;
        }
        
        return true;
    }
    
    /**
     * Retorna la documentación del contrato
     */
    public function getContractDocumentation() {
        return [
            'module' => 'test_gemini',
            'version' => '1.0.0',
            'description' => 'Test module for Gemini API integration',
            'input_contract' => [
                'prompt' => 'string (required, 1-5000 chars)',
                'max_tokens' => 'integer (optional, 1-1000)'
            ],
            'output_contract' => [
                'prompt' => 'string (echoed input)',
                'response' => 'string (AI response)',
                'tokens_used' => 'integer (positive)',
                'processing_time' => 'string (format: NNNms)'
            ],
            'invariants' => [
                'Controller class must exist',
                'Validator class must exist',
                'All inputs must pass validation',
                'All outputs must conform to contract'
            ]
        ];
    }
}
