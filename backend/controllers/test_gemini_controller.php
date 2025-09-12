<?php
/**
 * 📁 controllers/test_gemini_controller.php
 * Controlador de prueba para validar universalidad
 */

class TestGeminiController {
    
    public function handleRequest($data) {
        try {
            // Validar contrato
            $contract = new TestGeminiContract();
            if (!$contract->validateInput($data)) {
                return [
                    'success' => false,
                    'error' => 'Contract validation failed',
                    'code' => 400
                ];
            }
            
            // Validar datos específicos
            $validator = new TestGeminiValidator();
            if (!$validator->validate($data)) {
                return [
                    'success' => false,
                    'error' => 'Data validation failed',
                    'code' => 422
                ];
            }
            
            // Simular procesamiento con Gemini API
            $result = $this->simulateGeminiCall($data);
            
            // Validar resultado según contrato
            if (!$contract->validateOutput($result)) {
                return [
                    'success' => false,
                    'error' => 'Output contract validation failed',
                    'code' => 500
                ];
            }
            
            return [
                'success' => true,
                'data' => $result,
                'message' => 'Test Gemini module executed successfully',
                'timestamp' => date('Y-m-d H:i:s'),
                'module' => 'test_gemini'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
    
    private function simulateGeminiCall($data) {
        // Simulación de llamada a Gemini API
        return [
            'prompt' => $data['prompt'] ?? 'Test prompt',
            'response' => 'Simulated Gemini response: ' . ($data['prompt'] ?? 'Hello World'),
            'tokens_used' => rand(10, 100),
            'processing_time' => rand(100, 1000) . 'ms'
        ];
    }
    
    public function healthCheck() {
        return [
            'success' => true,
            'module' => 'test_gemini',
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
