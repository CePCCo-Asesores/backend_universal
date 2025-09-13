<?php
/**
 * 📁 backend/utils/assistant_registry.php
 * Registro de todos los asistentes disponibles
 * Cada asistente = configuración única
 */

class AssistantRegistry {
    
    private $assistants = [];
    
    public function __construct() {
        $this->loadAssistants();
    }
    
    /**
     * Carga todas las configuraciones de asistentes
     */
    private function loadAssistants() {
        // Cargar desde archivos de configuración
        $this->loadFromConfigFiles();
        
        // También se pueden cargar desde base de datos en el futuro
        // $this->loadFromDatabase();
    }
    
    /**
     * Carga asistentes desde archivos de configuración
     */
    private function loadFromConfigFiles() {
        $configDir = __DIR__ . '/../config/assistants/';
        
        if (is_dir($configDir)) {
            $files = glob($configDir . '*.json');
            
            foreach ($files as $file) {
                $config = json_decode(file_get_contents($file), true);
                if ($config && isset($config['id'])) {
                    $this->assistants[$config['id']] = $config;
                }
            }
        }
        
        // Si no hay archivos, cargar configuraciones por defecto
        if (empty($this->assistants)) {
            $this->loadDefaultAssistants();
        }
    }
    
    /**
     * Configuraciones por defecto de asistentes
     */
    private function loadDefaultAssistants() {
        // PROMPT ARCHITECT
        $this->assistants['prompt_architect'] = [
            'id' => 'prompt_architect',
            'name' => 'Prompt Architect',
            'description' => 'Optimiza ideas en prompts usando 9 pilares de ingeniería',
            'category' => 'AI Engineering',
            'version' => '1.0.0',
            'prompt' => $this->getPromptArchitectPrompt(),
            'variable_mapping' => [
                'IDEA_EN_LENGUAJE_NATURAL' => 'idea',
                'DOMINIO' => 'dominio',
                'AUDIENCIA' => 'audiencia',
                'OBJETIVO' => 'objetivo',
                'BASELINE' => 'baseline',
                'RESTRICCIONES' => 'restricciones',
                'CRITERIOS_DE_EXITO' => 'criterios_exito',
                'IDIOMA' => 'idioma',
                'TIPO_MODELO' => 'tipo_modelo',
                'PERFIL' => 'perfil'
            ],
            'ui_config' => [
                'title' => 'Arquitecto de Prompts Experto',
                'subtitle' => 'Convierte tu idea en un prompt optimizado profesional',
                'color_scheme' => 'blue',
                'icon' => '🏗️'
            ],
            'ui_fields' => [
                [
                    'name' => 'idea',
                    'label' => 'Tu Idea en Lenguaje Natural',
                    'type' => 'textarea',
                    'placeholder' => 'Describe tu idea o necesidad...',
                    'required' => true,
                    'rows' => 4
                ],
                [
                    'name' => 'dominio',
                    'label' => 'Dominio/Industria',
                    'type' => 'select',
                    'options' => [
                        'tecnologia' => 'Tecnología',
                        'marketing' => 'Marketing',
                        'educacion' => 'Educación',
                        'salud' => 'Salud',
                        'finanzas' => 'Finanzas',
                        'otro' => 'Otro'
                    ],
                    'required' => true
                ],
                [
                    'name' => 'audiencia',
                    'label' => 'Audiencia Objetivo',
                    'type' => 'text',
                    'placeholder' => 'ej: empresarios, estudiantes, desarrolladores',
                    'required' => true
                ],
                [
                    'name' => 'objetivo',
                    'label' => 'Objetivo Específico',
                    'type' => 'text',
                    'placeholder' => 'ej: aumentar ventas, mejorar productividad',
                    'required' => true
                ],
                [
                    'name' => 'baseline',
                    'label' => 'Estado Actual',
                    'type' => 'text',
                    'placeholder' => 'ej: proceso manual, sin sistema',
                    'required' => false
                ],
                [
                    'name' => 'restricciones',
                    'label' => 'Restricciones',
                    'type' => 'text',
                    'placeholder' => 'ej: presupuesto limitado, plazo corto',
                    'required' => false
                ],
                [
                    'name' => 'perfil',
                    'label' => 'Perfil de Generación',
                    'type' => 'select',
                    'options' => [
                        'corporativo' => 'Corporativo',
                        'creativo' => 'Creativo',
                        'tecnico' => 'Técnico'
                    ],
                    'default' => 'corporativo'
                ]
            ],
            'ai_settings' => [
                'temperature' => 0.3,
                'max_tokens' => 2048,
                'model' => 'gpt-4'
            ]
        ];
        
        // BACKEND GENERATOR
        $this->assistants['backend_generator'] = [
            'id' => 'backend_generator',
            'name' => 'Backend Generator',
            'description' => 'Genera módulos PHP para backend universal',
            'category' => 'Code Generation',
            'version' => '1.0.0',
            'prompt' => $this->getBackendGeneratorPrompt(),
            'variable_mapping' => [
                'MODULE_NAME' => 'module_name',
                'FUNCTIONALITY' => 'functionality',
                'DATABASE' => 'needs_database',
                'EXTERNAL_API' => 'needs_external_api',
                'ACTIONS' => 'actions'
            ],
            'ui_config' => [
                'title' => 'Generador de Backend Universal',
                'subtitle' => 'Crea módulos PHP completos para tu backend',
                'color_scheme' => 'green',
                'icon' => '🔧'
            ],
            'ui_fields' => [
                [
                    'name' => 'module_name',
                    'label' => 'Nombre del Módulo',
                    'type' => 'text',
                    'placeholder' => 'ej: user, payment, notification',
                    'required' => true
                ],
                [
                    'name' => 'functionality',
                    'label' => 'Funcionalidad Principal',
                    'type' => 'textarea',
                    'placeholder' => 'Describe qué debe hacer este módulo...',
                    'required' => true,
                    'rows' => 3
                ],
                [
                    'name' => 'needs_database',
                    'label' => 'Requiere Base de Datos',
                    'type' => 'checkbox',
                    'default' => false
                ],
                [
                    'name' => 'needs_external_api',
                    'label' => 'Usa APIs Externas',
                    'type' => 'checkbox',
                    'default' => false
                ],
                [
                    'name' => 'actions',
                    'label' => 'Acciones del Módulo',
                    'type' => 'multiselect',
                    'options' => [
                        'create' => 'Create',
                        'read' => 'Read',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'list' => 'List',
                        'search' => 'Search'
                    ],
                    'default' => ['create', 'read', 'update', 'delete']
                ]
            ],
            'ai_settings' => [
                'temperature' => 0.2,
                'max_tokens' => 3000,
                'model' => 'gpt-4'
            ]
        ];
        
        // BUSINESS ANALYST (ejemplo adicional)
        $this->assistants['business_analyst'] = [
            'id' => 'business_analyst',
            'name' => 'Business Analyst',
            'description' => 'Analiza viabilidad y estrategia de negocios',
            'category' => 'Business Strategy',
            'version' => '1.0.0',
            'prompt' => $this->getBusinessAnalystPrompt(),
            'variable_mapping' => [
                'BUSINESS_IDEA' => 'business_idea',
                'MARKET' => 'target_market',
                'BUDGET' => 'budget',
                'TIMELINE' => 'timeline'
            ],
            'ui_config' => [
                'title' => 'Analista de Negocio IA',
                'subtitle' => 'Evalúa la viabilidad de tu idea de negocio',
                'color_scheme' => 'purple',
                'icon' => '📊'
            ],
            'ui_fields' => [
                [
                    'name' => 'business_idea',
                    'label' => 'Idea de Negocio',
                    'type' => 'textarea',
                    'placeholder' => 'Describe tu idea de negocio...',
                    'required' => true,
                    'rows' => 4
                ],
                [
                    'name' => 'target_market',
                    'label' => 'Mercado Objetivo',
                    'type' => 'text',
                    'placeholder' => 'ej: empresas medianas, millennials urbanos',
                    'required' => true
                ],
                [
                    'name' => 'budget',
                    'label' => 'Presupuesto Disponible',
                    'type' => 'select',
                    'options' => [
                        'bajo' => 'Menos de $10K',
                        'medio' => '$10K - $100K',
                        'alto' => 'Más de $100K'
                    ],
                    'required' => true
                ],
                [
                    'name' => 'timeline',
                    'label' => 'Plazo Esperado',
                    'type' => 'select',
                    'options' => [
                        'corto' => '1-3 meses',
                        'medio' => '3-12 meses',
                        'largo' => 'Más de 1 año'
                    ],
                    'required' => true
                ]
            ],
            'ai_settings' => [
                'temperature' => 0.4,
                'max_tokens' => 2500,
                'model' => 'gpt-4'
            ]
        ];
    }
    
    /**
     * Obtiene un asistente por ID
     */
    public function getAssistant($id) {
        return $this->assistants[$id] ?? null;
    }
    
    /**
     * Obtiene todos los asistentes
     */
    public function getAllAssistants() {
        return $this->assistants;
    }
    
    /**
     * Obtiene asistentes por categoría
     */
    public function getAssistantsByCategory($category) {
        return array_filter($this->assistants, function($assistant) use ($category) {
            return $assistant['category'] === $category;
        });
    }
    
    /**
     * Registra un nuevo asistente
     */
    public function registerAssistant($config) {
        if (isset($config['id'])) {
            $this->assistants[$config['id']] = $config;
            return true;
        }
        return false;
    }
    
    /**
     * Prompt original completo del Prompt Architect
     */
    private function getPromptArchitectPrompt() {
        // AQUÍ va el prompt original COMPLETO sin modificaciones
        return 'Actúa como un "Arquitecto de Prompts Experto" y determinista. Tu única función es tomar la idea y el contexto del usuario y transformarlos en un **prompt** optimizado, robusto y listo para ejecutar, aplicando rigurosamente los 9 pilares de la ingeniería de prompts.

**Recordatorio Clave:** Tu primer output visible debe incluir la recomendación **Go / No-Go** con un breve justificante cuantitativo.

**IDEA DEL USUARIO:**  
`{{IDEA_EN_LENGUAJE_NATURAL}}`

**CONTEXTO ADICIONAL (parámetros de entrada):**
- Dominio / industria: `{{DOMINIO}}`
- Audiencia objetivo: `{{AUDIENCIA}}`
- Objetivo específico: `{{OBJETIVO}}`
- Estado actual / baseline: `{{BASELINE}}`
- Restricciones clave: `{{RESTRICCIONES}}`
- Métricas de éxito iniciales: `{{CRITERIOS_DE_EXITO}}`
- Idioma de entrada: `{{IDIOMA}}` (opcional)
- Tipo de modelo inferido (texto / imagen / voz / código): `{{TIPO_MODELO}}` (autodetectable si no se proporciona)
- Perfil de generación (ej. "corporativo", "creativo", "técnico"): `{{PERFIL}}` (opcional)

[... resto del prompt original completo ...]';
    }
    
    /**
     * Prompt para Backend Generator
     */
    private function getBackendGeneratorPrompt() {
        return 'Actúa como un experto desarrollador PHP especializado en arquitecturas modulares universales...
        
        **MÓDULO A GENERAR:**
        - Nombre: {{MODULE_NAME}}
        - Funcionalidad: {{FUNCTIONALITY}}
        - Base de datos: {{DATABASE}}
        - API externa: {{EXTERNAL_API}}
        - Acciones: {{ACTIONS}}
        
        Genera los 3 archivos PHP completos siguiendo la arquitectura universal establecida...';
    }
    
    /**
     * Prompt para Business Analyst
     */
    private function getBusinessAnalystPrompt() {
        return 'Actúa como un consultor de negocios senior con 20 años de experiencia...
        
        **ANÁLISIS REQUERIDO:**
        - Idea: {{BUSINESS_IDEA}}
        - Mercado: {{MARKET}}
        - Presupuesto: {{BUDGET}}
        - Tiempo: {{TIMELINE}}
        
        Proporciona análisis Go/No-Go con scorecard detallado...';
    }
}
