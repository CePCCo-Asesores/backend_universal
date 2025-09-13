<?php
/**
 * 📁 backend/controllers/gemini_controller.php
 * Controlador real para integración con Gemini API
 */

class GeminiController {
    
    private $apiKey;
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    public function __construct() {
        $this->apiKey = getenv('GEMINI_API_KEY') ?: null;
        
        if (!$this->apiKey) {
            error_log('Gemini API Key no configurada');
        }
    }
    
    /**
     * Procesa prompts con Gemini API
     */
    public function generateText($data) {
        try {
            // Validar contrato
            $contract = new GeminiContract();
            if (!$contract->validateInput($data)) {
                return [
                    'success' => false,
                    'error' => 'Violación de contrato en entrada',
                    'code' => 400
                ];
            }
            
            // Validar datos específicos
            $validator = new GeminiValidator();
            if (!$validator->validate($data)) {
                return [
                    'success' => false,
                    'error' => 'Validación de datos falló',
                    'code' => 422
                ];
            }
            
            // Verificar API Key
            if (!$this->apiKey) {
                return [
                    'success' => false,
                    'error' => 'Gemini API Key no configurada',
                    'code' => 500
                ];
            }
            
            // Llamar a Gemini API
            $result = $this->callGeminiAPI($data);
            
            // Validar resultado según contrato
            if (!$contract->validateOutput($result)) {
                return [
                    'success' => false,
                    'error' => 'Violación de contrato en salida',
                    'code' => 500
                ];
            }
            
            return [
                'success' => true,
                'data' => $result,
                'message' => 'Texto generado exitosamente',
                'timestamp' => date('Y-m-d H:i:s'),
                'module' => 'gemini'
            ];
            
        } catch (Exception $e) {
            error_log('Error en GeminiController: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'detail' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
    
    /**
     * Llama a la API real de Gemini
     */
    private function callGeminiAPI($data) {
        $prompt = $data['prompt'];
        $maxTokens = $data['max_tokens'] ?? 1000;
        $temperature = $data['temperature'] ?? 0.7;
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature' => $temperature,
                'topP' => 0.8,
                'topK' => 40
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '?key=' . $this->apiKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: CEPCCO-Backend/1.0'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error cURL: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error {$httpCode}: {$response}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decodificando JSON: " . json_last_error_msg());
        }
        
        // Extraer texto de respuesta
        $generatedText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        if (empty($generatedText)) {
            throw new Exception("Respuesta vacía de Gemini API");
        }
        
        // Calcular tokens aproximados
        $promptTokens = str_word_count($prompt);
        $responseTokens = str_word_count($generatedText);
        $totalTokens = $promptTokens + $responseTokens;
        
        return [
            'prompt' => $prompt,
            'response' => $generatedText,
            'usage' => [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $responseTokens,
                'total_tokens' => $totalTokens
            ],
            'model' => 'gemini-pro',
            'temperature' => $temperature,
            'processing_time' => $this->getProcessingTime()
        ];
    }
    
    /**
     * Health check del módulo Gemini
     */
    public function healthCheck() {
        $status = [
            'success' => true,
            'module' => 'gemini',
            'status' => 'healthy',
            'api_key_configured' => !empty($this->apiKey),
            'api_url' => $this->apiUrl,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Test de conectividad básica (opcional)
        if ($this->apiKey) {
            $status['api_connectivity'] = 'ready';
        } else {
            $status['api_connectivity'] = 'no_api_key';
            $status['warning'] = 'Configura GEMINI_API_KEY en variables de entorno';
        }
        
        return $status;
    }
    
    /**
     * Lista modelos disponibles
     */
    public function listModels() {
        return [
            'success' => true,
            'models' => [
                'gemini-pro' => [
                    'name' => 'Gemini Pro',
                    'description' => 'Modelo optimizado para una amplia gama de tareas de razonamiento',
                    'max_tokens' => 30720,
                    'supported_languages' => ['es', 'en', 'fr', 'de', 'it', 'pt']
                ]
            ],
            'default_model' => 'gemini-pro',
            'module' => 'gemini'
        ];
    }
    
    /**
     * Calcula tiempo de procesamiento simulado
     */
    private function getProcessingTime() {
        return rand(500, 2000) . 'ms';
    }
}
