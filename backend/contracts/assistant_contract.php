<?php
/**
 * 📁 backend/contracts/assistant_contract.php
 * Contrato universal para todos los asistentes
 */

class AssistantContract {
    
    /**
     * Valida los datos de entrada para cualquier asistente
     */
    public function validateInput($data) {
        // Verificar que es array
        if (!is_array($data)) {
            error_log("AssistantContract: Input debe ser array");
            return false;
        }
        
        // Verificar assistant_id requerido
        if (!isset($data['assistant_id']) || !is_string($data['assistant_id'])) {
            error_log("AssistantContract: 'assistant_id' es requerido y debe ser string");
            return false;
        }
        
        // Verificar assistant_id no vacío
        if (trim($data['assistant_id']) === '') {
            error_log("AssistantContract: 'assistant_id' no puede estar vacío");
            return false;
        }
        
        // Verificar que assistant_id sea válido (solo caracteres permitidos)
        if (!preg_match('/^[a-z_]+$/', $data['assistant_id'])) {
            error_log("AssistantContract: 'assistant_id' contiene caracteres inválidos");
            return false;
        }
        
        // Los demás campos son dinámicos según el asistente
        // La validación específica la hace cada asistente
        
        return true;
    }
    
    /**
     * Valida los datos de salida de cualquier asistente
     */
    public function validateOutput($data) {
        // Verificar que es array
        if (!is_array($data)) {
            error_log("AssistantContract: Output debe ser array");
            return false;
        }
        
        // Campos básicos que todo resultado debe tener
        $requiredFields = ['success', 'timestamp'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                error_log("AssistantContract: Campo requerido '{$field}' faltante en output");
                return false;
            }
        }
        
        // Verificar timestamp válido
        if (!is_string($data['timestamp']) || !strtotime($data['timestamp'])) {
            error_log("AssistantContract: 'timestamp' debe ser fecha válida");
            return false;
        }
        
        // Si success es true, debe haber data o content
        if ($data['success'] === true) {
            if (!isset($data['data']) && !isset($data['content']) && !isset($data['result'])) {
                error_log("AssistantContract: Output exitoso debe contener 'data', 'content' o 'result'");
                return false;
            }
        }
        
        // Si success es false, debe haber error
        if ($data['success'] === false) {
            if (!isset($data['error']) || !is_string($data['error']) || trim($data['error']) === '') {
                error_log("AssistantContract: Output fallido debe contener 'error' no vacío");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida la configuración de un asistente
     */
    public function validateAssistantConfig($config) {
        if (!is_array($config)) {
            error_log("AssistantContract: Config debe ser array");
            return false;
        }
        
        // Campos requeridos en configuración
        $requiredFields = ['id', 'name', 'prompt', 'variable_mapping', 'ui_fields'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                error_log("AssistantContract: Campo requerido '{$field}' faltante en config");
                return false;
            }
        }
        
        // Verificar ID válido
        if (!is_string($config['id']) || !preg_match('/^[a-z_]+$/', $config['id'])) {
            error_log("AssistantContract: 'id' debe ser string con formato válido");
            return false;
        }
        
        // Verificar nombre no vacío
        if (!is_string($config['name']) || trim($config['name']) === '') {
            error_log("AssistantContract: 'name' debe ser string no vacío");
            return false;
        }
        
        // Verificar prompt no vacío
        if (!is_string($config['prompt']) || trim($config['prompt']) === '') {
            error_log("AssistantContract: 'prompt' debe ser string no vacío");
            return false;
        }
        
        // Verificar variable_mapping es array
        if (!is_array($config['variable_mapping'])) {
            error_log("AssistantContract: 'variable_mapping' debe ser array");
            return false;
        }
        
        // Verificar ui_fields es array
        if (!is_array($config['ui_fields'])) {
            error_log("AssistantContract: 'ui_fields' debe ser array");
            return false;
        }
        
        // Validar estructura de ui_fields
        foreach ($config['ui_fields'] as $field) {
            if (!is_array($field)) {
                error_log("AssistantContract: Cada 'ui_field' debe ser array");
                return false;
            }
            
            if (!isset($field['name']) || !isset($field['type'])) {
                error_log("AssistantContract: 'ui_field' debe tener 'name' y 'type'");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verifica las invariantes del sistema de asistentes
     */
    public function checkInvariants() {
        // Verificar que las clases necesarias existen
        if (!class_exists('AssistantController')) {
            error_log("AssistantContract: AssistantController class no encontrada");
            return false;
        }
        
        if (!class_exists('AssistantRegistry')) {
            error_log("AssistantContract: AssistantRegistry class no encontrada");
            return false;
        }
        
        if (!class_exists('AssistantValidator')) {
            error_log("AssistantContract: AssistantValidator class no encontrada");
            return false;
        }
        
        // Verificar que las funciones de PHP necesarias están disponibles
        if (!function_exists('json_encode') || !function_exists('json_decode')) {
            error_log("AssistantContract: JSON functions no disponibles");
            return false;
        }
        
        return true;
    }
    
    /**
     * Retorna la documentación del contrato
     */
    public function getContractDocumentation() {
        return [
            'module' => 'assistant_universal',
            'version' => '1.0.0',
            'description' => 'Sistema universal para ejecutar múltiples asistentes especializados',
            'input_contract' => [
                'assistant_id' => 'string (required, formato: a-z_)',
                'dynamic_fields' => 'mixed (según configuración del asistente)'
            ],
            'output_contract' => [
                'success' => 'boolean (required)',
                'timestamp' => 'string (required, formato fecha)',
                'data|content|result' => 'mixed (required si success=true)',
                'error' => 'string (required si success=false)',
                'assistant' => 'string (opcional, ID del asistente)',
                'processing_time' => 'string (opcional)'
            ],
            'assistant_config_contract' => [
                'id' => 'string (required, formato: a-z_)',
                'name' => 'string (required, no vacío)',
                'prompt' => 'string (required, prompt completo)',
                'variable_mapping' => 'array (required, mapeo variables)',
                'ui_fields' => 'array (required, configuración UI)',
                'ui_config' => 'array (opcional, configuración visual)',
                'ai_settings' => 'array (opcional, configuración IA)'
            ],
            'invariants' => [
                'AssistantController class must exist',
                'AssistantRegistry class must exist', 
                'AssistantValidator class must exist',
                'JSON functions must be available',
                'All assistant configs must be valid',
                'All inputs must pass validation',
                'All outputs must conform to contract'
            ]
        ];
    }
}
