'use client'

import { useState } from 'react'

interface UserData {
  tipoUsuario: string
  neurodiversidades: string[]
  paso: number
  grado?: string
  contenidoTematico?: string
  temaDetonador?: string
  numeroSesiones?: number
  duracionSesion?: number
}

// Tipos para los props
interface StepProps {
  userData: UserData;
  setUserData: (data: UserData) => void;
}

export default function AsistenteND() {
  const [userData, setUserData] = useState<UserData>({
    tipoUsuario: '',
    neurodiversidades: [],
    paso: 1
  })

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-blue-50 to-indigo-50 p-4">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <header className="text-center mb-8">
          <div className="text-6xl mb-4">🧠</div>
          <h1 className="text-4xl font-bold text-gray-800 mb-2">
            Asistente de Planeación Inclusiva y Neurodivergente
          </h1>
          <p className="text-lg text-gray-600">
            Mi misión es ayudarte a crear actividades que celebren y potencien la diversidad neurológica
          </p>
          <div className="mt-4 text-sm text-purple-600 font-medium">
            ✨ Versión Maestra ND - Paso {userData.paso} de 6
          </div>
        </header>

        {/* Contenido principal */}
        <div className="bg-white rounded-xl shadow-lg p-8 border border-purple-100">
          {userData.paso === 1 && <PasoUno userData={userData} setUserData={setUserData} />}
          {userData.paso === 2 && <PasoDos userData={userData} setUserData={setUserData} />}
          {userData.paso === 3 && <PasoTres userData={userData} setUserData={setUserData} />}
          {userData.paso === 4 && <PasoCuatro userData={userData} setUserData={setUserData} />}
          {userData.paso === 5 && <PasoCinco userData={userData} setUserData={setUserData} />}
          {userData.paso === 6 && <PasoSeis userData={userData} setUserData={setUserData} />}
        </div>

        {/* Barra de progreso */}
        <div className="mt-6 bg-white rounded-lg p-4 shadow-sm">
          <div className="flex justify-between text-sm text-gray-600 mb-2">
            <span>Progreso</span>
            <span>{userData.paso}/6</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div 
              className="bg-gradient-to-r from-purple-500 to-blue-500 h-2 rounded-full transition-all duration-300"
              style={{ width: `${(userData.paso / 6) * 100}%` }}
            />
          </div>
        </div>
      </div>
    </div>
  )
}

// Paso 1: Identificación de usuario
function PasoUno({ userData, setUserData }: StepProps) {
  const tiposUsuario = [
    { id: 'docente', label: '1️⃣ Docente', descripcion: 'Trabajo con estudiantes en aula' },
    { id: 'terapeuta', label: '2️⃣ Terapeuta', descripcion: 'Trabajo con clientes/pacientes en sesiones' },
    { id: 'padre', label: '3️⃣ Padre/Madre', descripcion: 'Trabajo con mis hijos en rutinas familiares' },
    { id: 'medico', label: '4️⃣ Médico', descripcion: 'Trabajo con pacientes en consultorio' },
    { id: 'mixto', label: '6️⃣ Mixto', descripcion: 'Ej. madre-docente, terapeuta-padre' }
  ]

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          ¡Hola! 🧠 ¿Qué tipo de usuario eres?
        </h2>
        <p className="text-gray-600">
          Esto me ayuda a personalizar el lenguaje y las recomendaciones específicamente para ti.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {tiposUsuario.map((tipo) => (
          <button
            key={tipo.id}
            onClick={() => setUserData({...userData, tipoUsuario: tipo.id, paso: 2})}
            className="group flex items-start p-6 border-2 border-gray-200 rounded-xl hover:border-purple-400 hover:bg-purple-50 transition-all duration-200 text-left"
          >
            <div className="text-2xl mr-4 flex-shrink-0 mt-1">
              {tipo.id === 'docente' && '📚'}
              {tipo.id === 'terapeuta' && '❤️'}
              {tipo.id === 'padre' && '👥'}
              {tipo.id === 'medico' && '🩺'}
              {tipo.id === 'mixto' && '🔄'}
            </div>
            <div>
              <div className="font-semibold text-gray-800 mb-1">{tipo.label}</div>
              <div className="text-sm text-gray-600">{tipo.descripcion}</div>
            </div>
          </button>
        ))}
      </div>
    </div>
  )
}

// Paso 2: Identificación de neurodiversidad
function PasoDos({ userData, setUserData }: StepProps) {
  const [neurodiversidadesSeleccionadas, setNeurodiversidadesSeleccionadas] = useState<string[]>([])

  const neurodiversidades = [
    { id: 'tdah', label: '🧠 TDAH', descripcion: 'Trastorno por Déficit de Atención e Hiperactividad' },
    { id: 'autismo', label: '🌈 Autismo', descripcion: 'Trastorno del Espectro Autista' },
    { id: 'dislexia', label: '📖 Dislexia', descripcion: 'Dificultades específicas de lectura' },
    { id: 'discalculia', label: '🔢 Discalculia', descripcion: 'Dificultades con matemáticas' },
    { id: 'disgrafia', label: '✍️ Disgrafía', descripcion: 'Dificultades con la escritura' },
    { id: 'altas_capacidades', label: '🎯 Altas Capacidades', descripcion: 'Capacidades intelectuales superiores' },
    { id: 'procesamiento_sensorial', label: '🎭 Procesamiento Sensorial', descripcion: 'Sensibilidades sensoriales' },
    { id: 'ansiedad', label: '👥 Ansiedad', descripcion: 'Trastornos de ansiedad' },
    { id: 'ninguna', label: '🌟 Sin neurodiversidad específica', descripcion: 'Enfoque preventivo universal' },
    { id: 'no_seguro', label: '❓ No estoy seguro/a', descripcion: 'Necesito ayuda para identificar' }
  ]

  const toggleNeurodiversidad = (id: string) => {
    if (id === 'ninguna' || id === 'no_seguro') {
      setNeurodiversidadesSeleccionadas([id])
    } else {
      const nuevas = neurodiversidadesSeleccionadas.includes(id)
        ? neurodiversidadesSeleccionadas.filter(n => n !== id)
        : [...neurodiversidadesSeleccionadas.filter(n => n !== 'ninguna' && n !== 'no_seguro'), id]
      setNeurodiversidadesSeleccionadas(nuevas)
    }
  }

  const continuar = () => {
    setUserData({
      ...userData, 
      neurodiversidades: neurodiversidadesSeleccionadas,
      paso: 3
    })
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          ¿Qué tipo de neurodiversidad está presente? 🌈
        </h2>
        <p className="text-gray-600 mb-4">
          Puedes elegir varias opciones. Esto me ayuda a afinar las adaptaciones específicas.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
        {neurodiversidades.map((nd) => (
          <button
            key={nd.id}
            onClick={() => toggleNeurodiversidad(nd.id)}
            className={`flex items-start p-4 border-2 rounded-xl transition-all duration-200 text-left ${
              neurodiversidadesSeleccionadas.includes(nd.id)
                ? 'border-purple-400 bg-purple-50 text-purple-800'
                : 'border-gray-200 hover:border-purple-300 hover:bg-purple-25'
            }`}
          >
            <div className="mr-3 text-xl">{nd.label.split(' ')[0]}</div>
            <div>
              <div className="font-medium">{nd.label.substring(2)}</div>
              <div className="text-sm text-gray-600">{nd.descripcion}</div>
            </div>
          </button>
        ))}
      </div>

      {neurodiversidadesSeleccionadas.length > 0 && (
        <div className="flex justify-between items-center pt-4 border-t">
          <button
            onClick={() => setUserData({...userData, paso: 1})}
            className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
          >
            ← Volver
          </button>
          <button
            onClick={continuar}
            className="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium"
          >
            Continuar →
          </button>
        </div>
      )}
    </div>
  )
}

// Paso 3: Menú contextualizado  
function PasoTres({ userData, setUserData }: StepProps) {
  const opciones = [
    { id: 'adaptar', label: '🔄 Adaptar actividad existente', desc: 'Tengo una actividad que quiero hacer más inclusiva' },
    { id: 'crear', label: '✨ Crear actividad ND-amigable', desc: 'Diseñar una nueva actividad desde cero' },
    { id: 'revisar', label: '🔧 Revisar algo que no funcionó', desc: 'Analizar y mejorar una experiencia anterior' },
    { id: 'consultar', label: '💭 Consultar situación específica', desc: 'Tengo una pregunta o escenario particular' }
  ]

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Con esta información, ¿qué necesitas hacer hoy?
        </h2>
        <p className="text-gray-600">
          Basándome en que eres <strong>{userData.tipoUsuario}</strong> y trabajas con <strong>{userData.neurodiversidades.join(', ')}</strong>
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {opciones.map((opcion) => (
          <button
            key={opcion.id}
            onClick={() => setUserData({...userData, paso: 4})}
            className="flex flex-col items-start p-6 border-2 border-gray-200 rounded-xl hover:border-purple-400 hover:bg-purple-50 transition-all duration-200 text-left"
          >
            <div className="font-semibold text-gray-800 mb-2">{opcion.label}</div>
            <div className="text-sm text-gray-600">{opcion.desc}</div>
          </button>
        ))}
      </div>

      <div className="flex justify-between items-center pt-4 border-t">
        <button
          onClick={() => setUserData({...userData, paso: 2})}
          className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          ← Volver
        </button>
      </div>
    </div>
  )
}

// Paso 4: Recolección inteligente
function PasoCuatro({ userData, setUserData }: StepProps) {
  const [sensibilidades, setSensibilidades] = useState<string[]>([])

  const sensibilidadesSensoriales = [
    '🔊 Sonidos fuertes o inesperados',
    '💡 Luces brillantes o parpadeantes', 
    '🤲 Texturas específicas',
    '👥 Espacios muy concurridos',
    '⏰ Cambios bruscos de actividad',
    '🎵 Ruido de fondo constante'
  ]

  const toggleSensibilidad = (sens: string) => {
    setSensibilidades(prev => 
      prev.includes(sens) 
        ? prev.filter(s => s !== sens)
        : [...prev, sens]
    )
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Recolección Inteligente 🧠
        </h2>
        <p className="text-gray-600">
          ¿Hay alguna sensibilidad sensorial que deba considerar?
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
        {sensibilidadesSensoriales.map((sens) => (
          <button
            key={sens}
            onClick={() => toggleSensibilidad(sens)}
            className={`p-4 border-2 rounded-xl transition-all duration-200 text-left ${
              sensibilidades.includes(sens)
                ? 'border-orange-400 bg-orange-50 text-orange-800'
                : 'border-gray-200 hover:border-orange-300'
            }`}
          >
            {sens}
          </button>
        ))}
      </div>

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 className="font-medium text-blue-800 mb-2">💡 Verificación rápida de habilidades</h3>
        <p className="text-blue-700 text-sm">
          Incluiré una evaluación inicial sutil para adaptar el nivel de dificultad según las capacidades observadas.
        </p>
      </div>

      <div className="flex justify-between items-center pt-4 border-t">
        <button
          onClick={() => setUserData({...userData, paso: 3})}
          className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          ← Volver
        </button>
        <button
          onClick={() => setUserData({...userData, paso: 5})}
          className="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium"
        >
          Continuar →
        </button>
      </div>
    </div>
  )
}

// Paso 5: Personalización avanzada
function PasoCinco({ userData, setUserData }: StepProps) {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Personalización Avanzada 🎯
        </h2>
        <p className="text-gray-600">
          Últimos ajustes para personalizar tu experiencia
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="space-y-4">
          <h3 className="font-medium text-gray-800">🔍 Entornos de uso</h3>
          <div className="space-y-2">
            <label className="flex items-center space-x-2">
              <input type="checkbox" className="rounded" />
              <span className="text-sm">Casa/Hogar</span>
            </label>
            <label className="flex items-center space-x-2">
              <input type="checkbox" className="rounded" />
              <span className="text-sm">Escuela/Consultorio</span>
            </label>
            <label className="flex items-center space-x-2">
              <input type="checkbox" className="rounded" />
              <span className="text-sm">Espacios públicos</span>
            </label>
          </div>
        </div>

        <div className="space-y-4">
          <h3 className="font-medium text-gray-800">⏱️ Limitaciones</h3>
          <div className="space-y-2">
            <label className="flex items-center space-x-2">
              <input type="checkbox" className="rounded" />
              <span className="text-sm">Tiempo limitado (≤30 min)</span>
            </label>
            <label className="flex items-center space-x-2">
              <input type="checkbox" className="rounded" />
              <span className="text-sm">Pocos recursos materiales</span>
            </label>
            <label className="flex items-center space-x-2">
              <input type="checkbox" className="rounded" />
              <span className="text-sm">Implementación por otros</span>
            </label>
          </div>
        </div>
      </div>

      <div className="flex justify-between items-center pt-4 border-t">
        <button
          onClick={() => setUserData({...userData, paso: 4})}
          className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          ← Volver
        </button>
        <button
          onClick={() => setUserData({...userData, paso: 6})}
          className="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium"
        >
          Generar Planeación →
        </button>
      </div>
    </div>
  )
}

// Paso 6: Resultado final
function PasoSeis({ userData, setUserData }: StepProps) {
  const generarEjemploPlaneacion = () => {
    if (userData.tipoUsuario === 'docente') {
      return {
        titulo: "Planeación Inclusiva para el Aula",
        objetivo: "Actividad adaptada para estudiantes con " + userData.neurodiversidades.join(', '),
        duracion: "45 minutos",
        materiales: [
          "Material visual con pictogramas",
          "Espacios tranquilos para descanso sensorial",
          "Instrucciones paso a paso",
          "Opciones de comunicación alternativa"
        ],
        fases: [
          { nombre: "Preparación sensorial", tiempo: "5 min", actividad: "Establecer ambiente calmo, explicar la actividad con apoyo visual" },
          { nombre: "Actividad principal", tiempo: "25 min", actividad: "Desarrollo de la actividad con pausas flexibles" },
          { nombre: "Cierre y reflexión", tiempo: "10 min", actividad: "Retroalimentación visual y verbal" },
          { nombre: "Transición", tiempo: "5 min", actividad: "Preparación para siguiente actividad" }
        ]
      }
    }
    
    return {
      titulo: "Planeación ND Personalizada",
      objetivo: "Actividad adaptada para " + userData.tipoUsuario + " con enfoque en " + userData.neurodiversidades.join(', '),
      duracion: "Flexible según necesidades",
      materiales: ["Recursos adaptados", "Apoyos visuales", "Herramientas sensoriales"],
      fases: [
        { nombre: "Preparación", tiempo: "Variable", actividad: "Establecer contexto y expectativas claras" },
        { nombre: "Desarrollo", tiempo: "Variable", actividad: "Implementación con adaptaciones continuas" },
        { nombre: "Cierre", tiempo: "Variable", actividad: "Reflexión y generalización" }
      ]
    }
  }

  const planeacion = generarEjemploPlaneacion()

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          🎉 ¡Tu Planeación ND está lista!
        </h2>
        <p className="text-gray-600">
          Aquí tienes una propuesta personalizada que honra las fortalezas de la neurodiversidad
        </p>
      </div>

      <div className="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-xl p-6">
        <h3 className="text-xl font-bold text-purple-800 mb-4">{planeacion.titulo}</h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 className="font-medium text-gray-800 mb-2">🎯 Objetivo ND</h4>
            <p className="text-sm text-gray-600 mb-4">{planeacion.objetivo}</p>
            
            <h4 className="font-medium text-gray-800 mb-2">⏰ Duración estimada</h4>
            <p className="text-sm text-gray-600">{planeacion.duracion}</p>
          </div>
          
          <div>
            <h4 className="font-medium text-gray-800 mb-2">🛠️ Materiales ND</h4>
            <ul className="text-sm text-gray-600 space-y-1">
              {planeacion.materiales.map((material, index) => (
                <li key={index}>• {material}</li>
              ))}
            </ul>
          </div>
        </div>

        <div className="mt-6">
          <h4 className="font-medium text-gray-800 mb-3">📋 Fases de implementación</h4>
          <div className="space-y-3">
            {planeacion.fases.map((fase, index) => (
              <div key={index} className="bg-white rounded-lg p-3 border border-gray-100">
                <div className="flex justify-between items-start mb-1">
                  <span className="font-medium text-sm text-gray-800">{fase.nombre}</span>
                  <span className="text-xs text-purple-600 bg-purple-100 px-2 py-1 rounded">{fase.tiempo}</span>
                </div>
                <p className="text-xs text-gray-600">{fase.actividad}</p>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="bg-green-50 border border-green-200 rounded-lg p-4">
        <h4 className="font-medium text-green-800 mb-2">✅ Próximos pasos sugeridos:</h4>
        <ul className="text-sm text-green-700 space-y-1">
          <li>• Implementar en un entorno controlado primero</li>
          <li>• Observar y documentar respuestas</li>
          <li>• Ajustar según necesidades individuales</li>
          <li>• Compartir con otros cuidadores</li>
        </ul>
      </div>

      <div className="flex justify-between items-center pt-4 border-t">
        <button
          onClick={() => setUserData({...userData, paso: 1})}
          className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          🔄 Nueva planeación
        </button>
        <button
          onClick={() => setUserData({...userData, paso: 5})}
          className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          ← Ajustar opciones
        </button>
      </div>
    </div>
  )
}
