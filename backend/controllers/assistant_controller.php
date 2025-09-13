<?php
/**
 * 📁 backend/controllers/assistant_controller.php
 * Controlador Universal para todos los asistentes
 * Ejecuta cualquier asistente según configuración
 */

class AssistantController {
    
    private $assistantRegistry;
    private $promptProcessor;
    
    public function __construct() {
        $this->assistantRegistry = new AssistantRegistry();
        $this->promptProcessor = new PromptProcessor();
    }
    
    /**
     * Ejecuta un asistente específico
     * URL: /assistant/execute
     */
    public function execute($data) {
        try {
            // Validar estructura base
            $contract = new AssistantContract();
            if (!$contract->validateInput($data)) {
                return [
                    'success' => false,
                    'error' => 'Violación de contrato en entrada',
                    'code' => 400
                ];
            }
            
            // Validar datos específicos
            $validator = new AssistantValidator();
            if (!$validator->validate($data)) {
                return [
                    'success' => false,
                    'error' => 'Validación de datos falló',
                    'code' => 422
                ];
            }
            
            // Obtener configuración del asistente
            $assistantId = $data['assistant_id'];
            $assistantConfig = $this->assistantRegistry->getAssistant($assistantId);
            
            if (!$assistantConfig) {
                return [
                    'success' => false,
                    'error' => "Asistente '{$assistantId}' no encontrado",
                    'code' => 404
                ];
            }
            
            // Procesar con el asistente específico
            $result = $this->executeAssistant($assistantConfig, $data);
            
            // Validar resultado
            if (!$contract->validateOutput($result)) {
                return [
                    'success' => false,
                    'error' => 'Violación de contrato en salida',
                    'code' => 500
                ];
            }
            
            return [
                'success' => true,
                'assistant' => $assistantId,
                'data' => $result,
                'timestamp' => date('Y-m-d H:i:s'),
                'processing_time' => $this->getProcessingTime()
            ];
            
        } catch (Exception $e) {
            error_log('Error en AssistantController: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'detail' => $e->getMessage(),
                'code' => 500
            ];
        }
    }
    
    /**
     * Ejecuta un asistente específico con su prompt original
     */
    private function executeAssistant($config, $data) {
        // Obtener prompt original completo (SIN modificaciones)
        $originalPrompt = $config['prompt'];
        
        // Mapear variables del usuario a variables del prompt
        $variables = $this->mapUserDataToPromptVariables($data, $config['variable_mapping']);
        
        // Reemplazar variables en el prompt (manteniendo estructura original)
        $finalPrompt = $this->promptProcessor->replaceVariables($originalPrompt, $variables);
        
        // Log para debugging (opcional)
        error_log("Ejecutando asistente: {$config['name']}");
        error_log("Variables mapeadas: " . json_encode($variables));
        
        // Aquí se enviaría el prompt a la IA (GPT, Claude, etc.)
        // Por ahora simulamos el procesamiento
        $aiResult = $this->sendToAI($finalPrompt, $config['ai_settings']);
        
        // Procesar respuesta según formato del asistente
        return $this->processAIResponse($aiResult, $config);
    }
    
    /**
     * Mapea datos del usuario a variables del prompt
     */
    private function mapUserDataToPromptVariables($userData, $mapping) {
        $variables = [];
        
        foreach ($mapping as $promptVar => $userField) {
            if (isset($userData[$userField])) {
                $variables[$promptVar] = $userData[$userField];
            } else {
                // Valor por defecto si no está definido
                $variables[$promptVar] = $this->getDefaultValue($promptVar);
            }
        }
        
        return $variables;
    }
    
    /**
     * Simula envío a IA (aquí conectarías con GPT/Claude/etc.)
     */
    private function sendToAI($prompt, $aiSettings) {
        // SIMULACIÓN - En producción aquí iría la llamada real a la IA
        
        // Para desarrollo: simular respuesta realista
        if (strpos($prompt, 'Prompt Architect') !== false) {
            return $this->simulatePromptArchitectResponse($prompt);
        }
        
        if (strpos($prompt, 'Backend') !== false) {
            return $this->simulateBackendGeneratorResponse($prompt);
        }
        
        // Respuesta genérica
        return [
            'content' => 'Respuesta simulada del asistente',
            'metadata' => [
                'tokens_used' => 150,
                'model' => 'simulated-ai',
                'processing_time' => '1.2s'
            ]
        ];
    }
    
    /**
     * Simula respuesta del Prompt Architect
     */
    private function simulatePromptArchitectResponse($prompt) {
        return [
            'content' => [
                'go_no_go' => 'GO - Score: 8.5/10',
                'justificacion' => 'Idea viable con ROI proyectado del 300%',
                'prompt_optimizado' => 'Actúa como experto en [dominio específico]...',
                'resumen_ejecutivo' => 'La propuesta presenta alta viabilidad técnica y comercial.',
                'metadatos' => [
                    'dominio' => 'tecnología',
                    'audiencia' => 'empresarios',
                    'tipo_modelo' => 'texto',
                    'tokens_estimados' => 2048
                ]
            ],
            'metadata' => [
                'tokens_used' => 350,
                'model' => 'prompt-architect-ai',
                'processing_time' => '2.1s'
            ]
        ];
    }
    
    /**
     * Simula respuesta del Backend Generator
     */
    private function simulateBackendGeneratorResponse($prompt) {
        return [
            'content' => [
                'files_generated' => [
                    'controllers/user_controller.php',
                    'contracts/user_contract.php',
                    'validators/user_validator.php'
                ],
                'routes_yaml' => "/user/create:\n  controller: \"UserController\"\n  action: \"create\"",
                'setup_instructions' => [
                    'Crear los 3 archivos en las carpetas correspondientes',
                    'Agregar rutas al routes.yaml',
                    'Commit y push para deploy'
                ]
            ],
            'metadata' => [
                'tokens_used' => 280,
                'model' => 'backend-generator-ai',
                'processing_time' => '1.8s'
            ]
        ];
    }
    
    /**
     * Procesa respuesta de IA según configuración del asistente
     */
    private function processAIResponse($aiResult, $config) {
        // Aplicar transformaciones específicas del asistente si las hay
        $processed = $aiResult['content'];
        
        // Agregar metadatos estándar
        $processed['assistant_metadata'] = [
            'assistant_name' => $config['name'],
            'assistant_version' => $config['version'],
            'ai_model_used' => $aiResult['metadata']['model'] ?? 'unknown',
            'tokens_consumed' => $aiResult['metadata']['tokens_used'] ?? 0,
            'ai_processing_time' => $aiResult['metadata']['processing_time'] ?? '0s'
        ];
        
        return $processed;
    }
    
    /**
     * Lista todos los asistentes disponibles
     * URL: /assistant/list
     */
    public function listAssistants() {
        $assistants = $this->assistantRegistry->getAllAssistants();
        
        return [
            'success' => true,
            'assistants' => array_map(function($config) {
                return [
                    'id' => $config['id'],
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'category' => $config['category'],
                    'ui_fields' => $config['ui_fields'],
                    'version' => $config['version']
                ];
            }, $assistants),
            'total' => count($assistants),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Obtiene configuración de UI para un asistente
     * URL: /assistant/{id}/ui-config
     */
    public function getUIConfig($data) {
        $assistantId = $data['assistant_id'];
        $config = $this->assistantRegistry->getAssistant($assistantId);
        
        if (!$config) {
            return [
                'success' => false,
                'error' => "Asistente '{$assistantId}' no encontrado",
                'code' => 404
            ];
        }
        
        return [
            'success' => true,
            'assistant' => $assistantId,
            'ui_config' => $config['ui_config'],
            'fields' => $config['ui_fields'],
            'validation_rules' => $config['validation_rules'] ?? []
        ];
    }
    
    /**
     * Health check del módulo
     */
    public function healthCheck() {
        $assistantsCount = count($this->assistantRegistry->getAllAssistants());
        
        return [
            'success' => true,
            'module' => 'assistant_universal',
            'status' => 'healthy',
            'assistants_loaded' => $assistantsCount,
            'processor_status' => $this->promptProcessor->getStatus(),
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Valores por defecto para variables no definidas
     */
    private function getDefaultValue($variable) {
        $defaults = [
            'IDIOMA' => 'español',
            'TIPO_MODELO' => 'texto',
            'PERFIL' => 'profesional'
        ];
        
        return $defaults[$variable] ?? '';
    }
    
    /**
     * Tiempo de procesamiento simulado
     */
    private function getProcessingTime() {
        return rand(800, 2500) . 'ms';
    }
}
