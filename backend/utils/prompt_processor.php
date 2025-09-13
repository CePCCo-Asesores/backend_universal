<?php
/**
 * 📁 backend/utils/prompt_processor.php
 * Procesador de prompts - reemplaza variables manteniendo estructura original
 */

class PromptProcessor {
    
    private $status = 'ready';
    
    /**
     * Reemplaza variables en el prompt manteniendo estructura original
     * Variables en formato: {{VARIABLE_NAME}}
     */
    public function replaceVariables($prompt, $variables) {
        try {
            $processedPrompt = $prompt;
            
            // Log para debugging
            error_log("PromptProcessor: Iniciando reemplazo de variables");
            error_log("PromptProcessor: Variables recibidas: " . json_encode(array_keys($variables)));
            
            // Reemplazar cada variable
            foreach ($variables as $varName => $value) {
                $placeholder = '{{' . $varName . '}}';
                $varValue = $this->processVariableValue($value);
                
                // Contar ocurrencias antes del reemplazo
                $occurrences = substr_count($processedPrompt, $placeholder);
                
                if ($occurrences > 0) {
                    $processedPrompt = str_replace($placeholder, $varValue, $processedPrompt);
                    error_log("PromptProcessor: Reemplazadas {$occurrences} ocurrencias de {$placeholder}");
                } else {
                    error_log("PromptProcessor: Variable {$placeholder} no encontrada en prompt");
                }
            }
            
            // Verificar si quedaron variables sin reemplazar
            $unreplacedVars = $this->findUnreplacedVariables($processedPrompt);
            if (!empty($unreplacedVars)) {
                error_log("PromptProcessor: Variables sin reemplazar: " . implode(', ', $unreplacedVars));
                
                // Reemplazar variables no definidas con valores por defecto
                $processedPrompt = $this->replaceUnreplacedVariables($processedPrompt, $unreplacedVars);
            }
            
            error_log("PromptProcessor: Reemplazo completado exitosamente");
            return $processedPrompt;
            
        } catch (Exception $e) {
            error_log("PromptProcessor error: " . $e->getMessage());
            $this->status = 'error';
            return $prompt; // Retornar prompt original en caso de error
        }
    }
    
    /**
     * Procesa el valor de una variable para inserción
     */
    private function processVariableValue($value) {
        if (is_array($value)) {
            // Convertir array a string readable
            if ($this->isAssociativeArray($value)) {
                return $this->formatAssociativeArray($value);
            } else {
                return implode(', ', $value);
            }
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return 'no especificado';
        }
        
        if (is_numeric($value)) {
            return (string)$value;
        }
        
        // Para strings, sanitizar pero mantener contenido
        return $this->sanitizeVariableValue((string)$value);
    }
    
    /**
     * Sanitiza el valor de una variable
     */
    private function sanitizeVariableValue($value) {
        // Remover caracteres de control peligrosos pero mantener saltos de línea
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Normalizar espacios múltiples pero conservar estructura
        $value = preg_replace('/[ \t]+/', ' ', $value);
        
        // Conservar saltos de línea importantes
        $value = preg_replace('/\n{3,}/', "\n\n", $value);
        
        return trim($value);
    }
    
    /**
     * Verifica si un array es asociativo
     */
    private function isAssociativeArray($array) {
        if (!is_array($array)) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }
    
    /**
     * Formatea array asociativo para inserción en prompt
     */
    private function formatAssociativeArray($array) {
        $formatted = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $formatted[] = "- {$key}: " . implode(', ', $value);
            } else {
                $formatted[] = "- {$key}: {$value}";
            }
        }
        return implode("\n", $formatted);
    }
    
    /**
     * Encuentra variables no reemplazadas en el prompt
     */
    private function findUnreplacedVariables($prompt) {
        $pattern = '/\{\{([A-Z_]+)\}\}/';
        preg_match_all($pattern, $prompt, $matches);
        return array_unique($matches[1]);
    }
    
    /**
     * Reemplaza variables no definidas con valores por defecto
     */
    private function replaceUnreplacedVariables($prompt, $unreplacedVars) {
        $defaults = $this->getDefaultValues();
        
        foreach ($unreplacedVars as $varName) {
            $placeholder = '{{' . $varName . '}}';
            $defaultValue = $defaults[$varName] ?? '[no especificado]';
            
            $prompt = str_replace($placeholder, $defaultValue, $prompt);
            error_log("PromptProcessor: Variable {$varName} reemplazada con valor por defecto: {$defaultValue}");
        }
        
        return $prompt;
    }
    
    /**
     * Valores por defecto para variables comunes
     */
    private function getDefaultValues() {
        return [
            'IDIOMA' => 'español',
            'TIPO_MODELO' => 'texto',
            'PERFIL' => 'profesional',
            'BASELINE' => 'estado actual no especificado',
            'RESTRICCIONES' => 'sin restricciones específicas',
            'CRITERIOS_DE_EXITO' => 'mejora en eficiencia y calidad',
            'DOMINIO' => 'general',
            'AUDIENCIA' => 'usuarios generales',
            'OBJETIVO' => 'optimizar proceso',
            'IDEA_EN_LENGUAJE_NATURAL' => 'idea no especificada',
            'MODULE_NAME' => 'nuevo_modulo',
            'FUNCTIONALITY' => 'funcionalidad básica',
            'DATABASE' => 'false',
            'EXTERNAL_API' => 'false',
            'ACTIONS' => 'create, read, update, delete',
            'BUSINESS_IDEA' => 'idea de negocio no especificada',
            'MARKET' => 'mercado general',
            'BUDGET' => 'presupuesto no especificado',
            'TIMELINE' => 'plazo no especificado'
        ];
    }
    
    /**
     * Valida que el prompt procesado sea coherente
     */
    public function validateProcessedPrompt($prompt) {
        // Verificar que no hay variables sin reemplazar críticas
        $criticalVars = $this->findUnreplacedVariables($prompt);
        if (!empty($criticalVars)) {
            error_log("PromptProcessor: Variables críticas sin reemplazar: " . implode(', ', $criticalVars));
            return false;
        }
        
        // Verificar longitud mínima y máxima
        $length = strlen($prompt);
        if ($length < 100) {
            error_log("PromptProcessor: Prompt demasiado corto ({$length} chars)");
            return false;
        }
        
        if ($length > 100000) {
            error_log("PromptProcessor: Prompt demasiado largo ({$length} chars)");
            return false;
        }
        
        // Verificar que tiene estructura coherente
        if (!$this->hasCoherentStructure($prompt)) {
            error_log("PromptProcessor: Prompt no tiene estructura coherente");
            return false;
        }
        
        return true;
    }
    
    /**
     * Verifica que el prompt tenga estructura coherente
     */
    private function hasCoherentStructure($prompt) {
        // Verificar que contiene instrucciones básicas
        $requiredPatterns = [
            '/Actúa como|Act as|Eres un|You are/i',  // Declaración de rol
            '/objetivo|goal|purpose|función/i'        // Objetivo claro
        ];
        
        foreach ($requiredPatterns as $pattern) {
            if (!preg_match($pattern, $prompt)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Genera estadísticas del procesamiento
     */
    public function getProcessingStats($originalPrompt, $processedPrompt, $variables) {
        return [
            'original_length' => strlen($originalPrompt),
            'processed_length' => strlen($processedPrompt),
            'variables_provided' => count($variables),
            'variables_replaced' => $this->countReplacedVariables($originalPrompt, $processedPrompt),
            'unreplaced_variables' => $this->findUnreplacedVariables($processedPrompt),
            'processing_time' => microtime(true),
            'status' => $this->status
        ];
    }
    
    /**
     * Cuenta variables que fueron reemplazadas
     */
    private function countReplacedVariables($original, $processed) {
        $originalVars = $this->findUnreplacedVariables($original);
        $processedVars = $this->findUnreplacedVariables($processed);
        
        return count($originalVars) - count($processedVars);
    }
    
    /**
     * Retorna el estado del procesador
     */
    public function getStatus() {
        return $this->status;
    }
    
    /**
     * Reinicia el estado del procesador
     */
    public function reset() {
        $this->status = 'ready';
    }
    
    /**
     * Genera un prompt de prueba para debugging
     */
    public function generateTestPrompt() {
        return 'Actúa como {{ROL_EXPERTO}} especializado en {{DOMINIO}}.

Tu audiencia son {{AUDIENCIA}} y tu objetivo es {{OBJETIVO}}.

Analiza la siguiente idea: {{IDEA_EN_LENGUAJE_NATURAL}}

Contexto adicional:
- Estado actual: {{BASELINE}}
- Restricciones: {{RESTRICCIONES}}
- Idioma: {{IDIOMA}}
- Perfil: {{PERFIL}}

Proporciona una respuesta estructurada y profesional.';
    }
    
    /**
     * Prueba el procesador con datos de ejemplo
     */
    public function runTest() {
        $testPrompt = $this->generateTestPrompt();
        $testVariables = [
            'ROL_EXPERTO' => 'consultor de negocios',
            'DOMINIO' => 'tecnología',
            'AUDIENCIA' => 'empresarios',
            'OBJETIVO' => 'validar viabilidad',
            'IDEA_EN_LENGUAJE_NATURAL' => 'crear una app móvil',
            'BASELINE' => 'sin aplicación actual',
            'RESTRICCIONES' => 'presupuesto limitado',
            'IDIOMA' => 'español',
            'PERFIL' => 'corporativo'
        ];
        
        $processed = $this->replaceVariables($testPrompt, $testVariables);
        $stats = $this->getProcessingStats($testPrompt, $processed, $testVariables);
        
        return [
            'test_successful' => $this->validateProcessedPrompt($processed),
            'original_prompt' => $testPrompt,
            'processed_prompt' => $processed,
            'statistics' => $stats
        ];
    }
}
