'use client'

import { useState } from 'react'

interface UserData {
  tipoUsuario: string
  neurodiversidades: string[]
  paso: number
  opcionMenu?: string
  grado?: string
  contenidoTematico?: string
  temaDetonador?: string
  numeroSesiones?: number
  duracionSesion?: number
  sensibilidadesSensoriales?: string[]
  entornos?: string[]
  limitacionesTiempo?: boolean
  otrosCuidadores?: boolean
  prioridadUrgente?: string
  formatoPreferido?: string
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
      <div className="max-w-5xl mx-auto">
        {/* Header */}
        <header className="text-center mb-8">
          <div className="text-6xl mb-4">🧠</div>
          <h1 className="text-4xl font-bold text-gray-800 mb-2">
            Asistente de Planeación Inclusiva y Neurodivergente
          </h1>
          <p className="text-lg text-gray-600 mb-2">
            Mi misión es ayudarte a crear actividades que celebren y potencien la diversidad neurológica
          </p>
          <p className="text-sm text-purple-600 font-medium">
            VERSIÓN MAESTRA ND - Experto en Diseño Universal para el Aprendizaje (DUA)
          </p>
          <div className="mt-4 text-sm text-purple-600 font-medium">
            ✨ Paso {userData.paso} de 7
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
          {userData.paso === 7 && <PasoSiete userData={userData} setUserData={setUserData} />}
        </div>

        {/* Barra de progreso */}
        <div className="mt-6 bg-white rounded-lg p-4 shadow-sm">
          <div className="flex justify-between text-sm text-gray-600 mb-2">
            <span>Progreso</span>
            <span>{userData.paso}/7</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div 
              className="bg-gradient-to-r from-purple-500 to-blue-500 h-2 rounded-full transition-all duration-300"
              style={{ width: `${(userData.paso / 7) * 100}%` }}
            />
          </div>
        </div>

        {/* Principio ND */}
        <div className="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
          <p className="text-sm text-blue-800">
            <strong>PRINCIPIO ND CLAVE:</strong> La neurodiversidad es una variación natural del cerebro humano. 
            Nunca se trata como déficit. Toda propuesta es afirmativa, sensorialmente consciente y éticamente defendible.
          </p>
        </div>
      </div>
    </div>
  )
}

// Paso 1: Saludo e Identificación
function PasoUno({ userData, setUserData }: StepProps) {
  const tiposUsuario = [
    { id: 'docente', label: '1️⃣ Docente', descripcion: 'Trabajo con estudiantes en aula, lecciones' },
    { id: 'terapeuta', label: '2️⃣ Terapeuta', descripcion: 'Trabajo con clientes/pacientes en sesiones' },
    { id: 'padre', label: '3️⃣ Padre/Madre', descripcion: 'Trabajo con hijos en rutinas familiares cotidianas' },
    { id: 'medico', label: '4️⃣ Médico', descripcion: 'Trabajo con pacientes en consultorio, tratamientos' },
    { id: 'otro', label: '5️⃣ Otro (especifica)', descripcion: 'Describe tu rol y te adaptaré el lenguaje' },
    { id: 'mixto', label: '6️⃣ Mixto', descripcion: 'Ej. madre-docente, terapeuta-padre' }
  ]

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          ¡Hola! 🧠 Soy tu Asistente de Planeación Inclusiva y Neurodivergente
        </h2>
        <p className="text-gray-600 mb-4">
          Mi misión es ayudarte a crear actividades que celebren y potencien la diversidad neurológica.
        </p>
        <p className="text-lg font-medium text-gray-800">
          ¿Qué tipo de usuario eres?
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
              {tipo.id === 'otro' && '🔍'}
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

// Paso 2: Identificación de Neurodiversidad (Paso 1.5 del original)
function PasoDos({ userData, setUserData }: StepProps) {
  const [neurodiversidadesSeleccionadas, setNeurodiversidadesSeleccionadas] = useState<string[]>([])

  const neurodiversidades = [
    { id: 'tdah', label: '🧠 TDAH', descripcion: 'Trastorno por Déficit de Atención e Hiperactividad' },
    { id: 'autismo', label: '🌈 Autismo', descripcion: 'Trastorno del Espectro Autista' },
    { id: 'dislexia', label: '📖 Dislexia', descripcion: 'Dificultades específicas de lectura' },
    { id: 'discalculia', label: '🔢 Discalculia', descripcion: 'Dificultades con matemáticas' },
    { id: 'disgrafia', label: '✍️ Disgrafía', descripcion: 'Dificultades con la escritura' },
    { id: 'altas_capacidades', label: '🎯 Altas Capacidades', descripcion: 'Capacidades intelectuales superiores' },
    { id: 'tourette', label: '⚡ Tourette', descripcion: 'Síndrome de Tourette' },
    { id: 'dispraxia', label: '🔄 Dispraxia', descripcion: 'Dificultades de coordinación motora' },
    { id: 'procesamiento_sensorial', label: '🎭 Procesamiento Sensorial', descripcion: 'Sensibilidades sensoriales específicas' },
    { id: 'ansiedad', label: '👥 Ansiedad', descripcion: 'Trastornos de ansiedad' },
    { id: 'ninguna', label: '🌟 Sin neurodiversidad específica', descripcion: 'Enfoque preventivo universal' },
    { id: 'otra', label: '🔍 Otra', descripcion: 'Especificar otra neurodiversidad' },
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
          Perfecto. Para afinar las adaptaciones, ¿qué tipo de neurodiversidad está presente?
        </h2>
        <p className="text-gray-600 mb-4">
          Puedes elegir varias opciones. ¿Hay alguna prioritaria o prefieres enfoque integrado?
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
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
            <div className="mr-3 text-lg">{nd.label.split(' ')[0]}</div>
            <div>
              <div className="font-medium text-sm">{nd.label.substring(2)}</div>
              <div className="text-xs text-gray-600">{nd.descripcion}</div>
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

// Paso 3: Menú Contextualizado
function PasoTres({ userData, setUserData }: StepProps) {
  const opciones = [
    { 
      id: 'adaptar', 
      label: '1️⃣ Adaptar actividad existente para ' + userData.neurodiversidades.join(', '), 
      icon: '🔄',
      desc: 'Mejorar una actividad que ya tienes para hacerla más inclusiva' 
    },
    { 
      id: 'crear', 
      label: '2️⃣ Crear actividad ND-amigable desde cero', 
      icon: '✨',
      desc: 'Diseñar una nueva actividad completamente neurodivergente-amigable' 
    },
    { 
      id: 'revisar', 
      label: '3️⃣ Revisar algo que no funcionó', 
      icon: '🔧',
      desc: 'Analizar y mejorar una experiencia anterior que tuvo dificultades' 
    },
    { 
      id: 'consultar', 
      label: '4️⃣ Consultar situación específica', 
      icon: '💭',
      desc: 'Tengo una pregunta o escenario particular sobre neurodiversidad' 
    },
    { 
      id: 'evaluar', 
      label: '5️⃣ Evaluar si alguien podría ser neurodivergente', 
      icon: '🔍',
      desc: 'Ayuda para identificar posibles señales de neurodiversidad' 
    },
    { 
      id: 'universal', 
      label: '6️⃣ Activar diseño universal preventivo', 
      icon: '🌐',
      desc: 'Crear entornos inclusivos para toda la neurodiversidad' 
    }
  ]

  const seleccionarOpcion = (opcionId: string) => {
    // Lógica especial para docentes que eligen "crear"
    if (userData.tipoUsuario === 'docente' && opcionId === 'crear') {
      setUserData({...userData, opcionMenu: opcionId, paso: 3.5}) // Paso especial
    } else {
      setUserData({...userData, opcionMenu: opcionId, paso: 4})
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Con esta información, ¿qué necesitas hacer hoy?
        </h2>
        <p className="text-gray-600">
          Eres <strong>{userData.tipoUsuario}</strong> trabajando con <strong>{userData.neurodiversidades.join(', ')}</strong>
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {opciones.map((opcion) => (
          <button
            key={opcion.id}
            onClick={() => seleccionarOpcion(opcion.id)}
            className="flex items-start p-6 border-2 border-gray-200 rounded-xl hover:border-purple-400 hover:bg-purple-50 transition-all duration-200 text-left"
          >
            <div className="text-2xl mr-4 flex-shrink-0">{opcion.icon}</div>
            <div>
              <div className="font-semibold text-gray-800 mb-2">{opcion.label}</div>
              <div className="text-sm text-gray-600">{opcion.desc}</div>
            </div>
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

// Paso 3.5: Activación de Planeación ND (solo para docentes que eligen "crear")
function PasoTresYMedio({ userData, setUserData }: StepProps) {
  const [respuestas, setRespuestas] = useState({
    grado: '',
    contenidoTematico: '',
    temaDetonador: '',
    numeroSesiones: '',
    duracionSesion: ''
  })

  const actualizarRespuesta = (campo: string, valor: string) => {
    setRespuestas(prev => ({...prev, [campo]: valor}))
  }

  const continuar = () => {
    setUserData({
      ...userData,
      grado: respuestas.grado,
      contenidoTematico: respuestas.contenidoTematico,
      temaDetonador: respuestas.temaDetonador,
      numeroSesiones: parseInt(respuestas.numeroSesiones),
      duracionSesion: parseInt(respuestas.duracionSesion),
      paso: 4
    })
  }

  const todasCompletas = Object.values(respuestas).every(r => r.trim() !== '')

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Activación de Planeación ND para Docentes
        </h2>
        <p className="text-gray-600 mb-4">
          Puedo ayudarte a generar una planeación neurodivergente completa. Responde estas preguntas una por una:
        </p>
      </div>

      <div className="space-y-6">
        <div>
          <label className="flex items-center text-lg font-medium text-gray-800 mb-2">
            1️⃣ 📚 ¿Cuál es el grado escolar al que va dirigida la planeación?
          </label>
          <input
            type="text"
            value={respuestas.grado}
            onChange={(e) => actualizarRespuesta('grado', e.target.value)}
            placeholder="Ej: 3° primaria, 1° secundaria, preescolar..."
            className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none"
          />
        </div>

        <div>
          <label className="flex items-center text-lg font-medium text-gray-800 mb-2">
            2️⃣ 🧪 ¿Cuál es el contenido temático principal?
          </label>
          <input
            type="text"
            value={respuestas.contenidoTematico}
            onChange={(e) => actualizarRespuesta('contenidoTematico', e.target.value)}
            placeholder="Ej: Matemáticas, Ciencias Naturales, Lenguaje..."
            className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none"
          />
        </div>

        <div>
          <label className="flex items-center text-lg font-medium text-gray-800 mb-2">
            3️⃣ 🎯 ¿Cuál es el tema detonador específico?
          </label>
          <input
            type="text"
            value={respuestas.temaDetonador}
            onChange={(e) => actualizarRespuesta('temaDetonador', e.target.value)}
            placeholder="Ej: Fracciones, El sistema solar, Comprensión lectora..."
            className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none"
          />
        </div>

        <div>
          <label className="flex items-center text-lg font-medium text-gray-800 mb-2">
            4️⃣ ⏳ ¿Cuántas sesiones tienes previstas para abordar este tema?
          </label>
          <input
            type="number"
            value={respuestas.numeroSesiones}
            onChange={(e) => actualizarRespuesta('numeroSesiones', e.target.value)}
            placeholder="Ej: 3, 5, 8..."
            min="1"
            className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none"
          />
        </div>

        <div>
          <label className="flex items-center text-lg font-medium text-gray-800 mb-2">
            5️⃣ 🕒 ¿Cuánto tiempo dura cada sesión? (en minutos)
          </label>
          <input
            type="number"
            value={respuestas.duracionSesion}
            onChange={(e) => actualizarRespuesta('duracionSesion', e.target.value)}
            placeholder="Ej: 45, 60, 90..."
            min="1"
            className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none"
          />
        </div>
      </div>

      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p className="text-blue-800 text-sm">
          Cuando tenga esta información, generaré la planeación ND adaptada a las neurodiversidades que mencionaste: <strong>{userData.neurodiversidades.join(', ')}</strong>
        </p>
      </div>

      <div className="flex justify-between items-center pt-4 border-t">
        <button
          onClick={() => setUserData({...userData, paso: 3})}
          className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          ← Volver
        </button>
        {todasCompletas && (
          <button
            onClick={continuar}
            className="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium"
          >
            Continuar →
          </button>
        )}
      </div>
    </div>
  )
}

// Paso 4: Recolección Inteligente
function PasoCuatro({ userData, setUserData }: StepProps) {
  const [sensibilidades, setSensibilidades] = useState<string[]>([])
  const [verificacionHabilidades, setVerificacionHabilidades] = useState<boolean | null>(null)

  const sensibilidadesSensoriales = [
    '🔊 Sonidos fuertes o inesperados',
    '💡 Luces brillantes o parpadeantes', 
    '🤲 Texturas específicas (ásperas, viscosas)',
    '👥 Espacios muy concurridos',
    '⏰ Cambios bruscos de actividad',
    '🎵 Ruido de fondo constante',
    '🌡️ Temperaturas extremas',
    '👃 Olores intensos'
  ]

  const toggleSensibilidad = (sens: string) => {
    setSensibilidades(prev => 
      prev.includes(sens) 
        ? prev.filter(s => s !== sens)
        : [...prev, sens]
    )
  }

  const continuar = () => {
    setUserData({
      ...userData,
      sensibilidadesSensoriales: sensibilidades,
      paso: 5
    })
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Recolección Inteligente 🧠
        </h2>
        <p className="text-gray-600 mb-4">
          Una pregunta por vez. Validación breve. Evaluación sensorial automática.
        </p>
      </div>

      <div className="space-y-6">
        <div>
          <h3 className="text-lg font-medium text-gray-800 mb-3">
            🔍 ¿Debe funcionar en múltiples entornos?
          </h3>
          <div className="grid grid-cols-1 gap-2">
            {opcionesEntornos.map((entorno) => (
              <button
                key={entorno}
                onClick={() => toggleEntorno(entorno)}
                className={`p-3 border-2 rounded-lg transition-all duration-200 text-left text-sm ${
                  entornos.includes(entorno)
                    ? 'border-blue-400 bg-blue-50 text-blue-800'
                    : 'border-gray-200 hover:border-blue-300'
                }`}
              >
                {entorno}
              </button>
            ))}
          </div>
        </div>
        
        <div>
          <h3 className="text-lg font-medium text-gray-800 mb-3">
            ⚠️ Limitaciones o consideraciones especiales
          </h3>
          <div className="grid grid-cols-1 gap-2">
            {opcionesLimitaciones.map((limitacion) => (
              <button
                key={limitacion}
                onClick={() => toggleLimitacion(limitacion)}
                className={`p-3 border-2 rounded-lg transition-all duration-200 text-left text-sm ${
                  limitaciones.includes(limitacion)
                    ? 'border-yellow-400 bg-yellow-50 text-yellow-800'
                    : 'border-gray-200 hover:border-yellow-300'
                }`}
              >
                {limitacion}
              </button>
            ))}
          </div>
        </div>
      </div>

      <div>
        <h3 className="text-lg font-medium text-gray-800 mb-3">
          🎯 ¿Hay alguna prioridad urgente?
        </h3>
        <textarea
          value={prioridad}
          onChange={(e) => setPrioridad(e.target.value)}
          placeholder="Describe cualquier situación urgente o prioridad específica que deba considerar..."
          className="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-purple-400 focus:outline-none h-24 resize-none"
        />
      </div>

      <div className="flex justify-between items-center pt-4 border-t">
        <button
          onClick={() => setUserData({...userData, paso: 4})}
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
    </div>
  )
}

// Paso 6: Selección de Formato
function PasoSeis({ userData, setUserData }: StepProps) {
  const [formatoSeleccionado, setFormatoSeleccionado] = useState('')

  const formatos = [
    {
      id: 'practico',
      titulo: '🎯 Versión Práctica',
      subtitulo: 'Lista para usar hoy',
      descripcion: 'Implementación inmediata con instrucciones claras y directas',
      tiempo: '⚡ 5-10 minutos de preparación'
    },
    {
      id: 'completo',
      titulo: '⚡ Versión Completa',
      subtitulo: 'Adaptaciones para diferentes situaciones',
      descripcion: 'Múltiples variantes y adaptaciones según contexto',
      tiempo: '📖 15-20 minutos de revisión'
    },
    {
      id: 'nd_plus',
      titulo: '🧠 Versión ND Plus',
      subtitulo: 'Generalización + capacitación + cronograma',
      descripcion: 'Incluye formación para cuidadores y plan de implementación extendido',
      tiempo: '📚 30+ minutos de estudio completo'
    },
    {
      id: 'sensorial',
      titulo: '🌈 Versión Sensorial',
      subtitulo: 'Enfoque especial en adaptaciones sensoriales',
      descripcion: 'Diseño específico para sensibilidades y procesamiento sensorial',
      tiempo: '🎭 Adaptaciones sensoriales detalladas'
    },
    {
      id: 'semaforo',
      titulo: '📊 Versión Semáforo ND',
      subtitulo: 'Verde = listo, Amarillo = requiere ajuste, Rojo = no viable aún',
      descripcion: 'Evaluación por fases con indicadores claros de viabilidad',
      tiempo: '🚦 Sistema de alertas y progreso'
    }
  ]

  const continuar = () => {
    setUserData({
      ...userData,
      formatoPreferido: formatoSeleccionado,
      paso: 7
    })
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Selección de Formato 📋
        </h2>
        <p className="text-gray-600 mb-4">
          ¿Qué formato prefieres para tu planeación ND?
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {formatos.map((formato) => (
          <button
            key={formato.id}
            onClick={() => setFormatoSeleccionado(formato.id)}
            className={`p-6 border-2 rounded-xl transition-all duration-200 text-left ${
              formatoSeleccionado === formato.id
                ? 'border-purple-400 bg-purple-50'
                : 'border-gray-200 hover:border-purple-300 hover:bg-purple-25'
            }`}
          >
            <div className="font-bold text-lg text-gray-800 mb-1">
              {formato.titulo}
            </div>
            <div className="font-medium text-purple-600 mb-2">
              {formato.subtitulo}
            </div>
            <div className="text-sm text-gray-600 mb-3">
              {formato.descripcion}
            </div>
            <div className="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
              {formato.tiempo}
            </div>
          </button>
        ))}
      </div>

      {formatoSeleccionado && (
        <div className="flex justify-between items-center pt-4 border-t">
          <button
            onClick={() => setUserData({...userData, paso: 5})}
            className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
          >
            ← Volver
          </button>
          <button
            onClick={continuar}
            className="px-8 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium"
          >
            Generar Planeación ND →
          </button>
        </div>
      )}
    </div>
  )
}

// Paso 7: Generación ND Especializada
function PasoSiete({ userData, setUserData }: StepProps) {
  const generarPlaneacionCompleta = () => {
    const base = {
      titulo: `Planeación ND para ${userData.tipoUsuario}`,
      usuario: userData.tipoUsuario,
      neurodiversidades: userData.neurodiversidades,
      formato: userData.formatoPreferido
    }

    // Sección 1: Comprensión ND
    const comprensionND = {
      explicacion: `Esta actividad honra la neurodiversidad reconociendo que ${userData.neurodiversidades.join(', ')} son variaciones naturales del cerebro humano, no déficits a corregir.`,
      fortalezas: getFortalezasND(userData.neurodiversidades)
    }

    // Sección 2: Evaluaciones Previas
    const evaluaciones = {
      sensorial: userData.sensibilidadesSensoriales || [],
      habilidades: "Verificación rápida incluida según configuración",
      ambiental: userData.entornos || []
    }

    // Sección 3: Implementación
    const implementacion = {
      objetivo: getObjetivoND(userData),
      materiales: getMaterialesND(userData),
      preparacion: getPreparacionEntorno(userData),
      instrucciones: getInstruccionesAdaptadas(userData),
      apoyosVisuales: "Pictogramas, esquemas visuales, códigos de color",
      tiempoEstimado: getTiempoEstimado(userData)
    }

    return {
      base,
      comprensionND,
      evaluaciones, 
      implementacion,
      generalizacion: getGeneralizacion(userData),
      capacitacion: getCapacitacion(userData),
      tecnologia: getTecnologiaND(userData)
    }
  }

  const planeacion = generarPlaneacionCompleta()

  return (
    <div className="space-y-6">
      <div className="text-center">
        <h2 className="text-3xl font-bold text-gray-800 mb-3">
          🎉 ¡Tu Planeación ND está lista!
        </h2>
        <p className="text-lg text-gray-600 mb-2">
          Aquí tienes tu propuesta ND para {userData.opcionMenu}
        </p>
        <p className="text-sm text-purple-600">
          Honra las fortalezas únicas de {userData.neurodiversidades.join(', ')} con adaptaciones sensoriales
        </p>
      </div>

      {/* Sección 1: Comprensión ND */}
      <div className="bg-purple-50 border border-purple-200 rounded-xl p-6">
        <h3 className="text-xl font-bold text-purple-800 mb-4">
          🧠 SECCIÓN 1: COMPRENSIÓN ND
        </h3>
        <div className="space-y-3">
          <div>
            <h4 className="font-medium text-gray-800 mb-2">Explicación ND:</h4>
            <p className="text-sm text-gray-700">{planeacion.comprensionND.explicacion}</p>
          </div>
          <div>
            <h4 className="font-medium text-gray-800 mb-2">Fortalezas que se potencian:</h4>
            <ul className="text-sm text-gray-700 space-y-1">
              {planeacion.comprensionND.fortalezas.map((fortaleza, index) => (
                <li key={index}>✨ {fortaleza}</li>
              ))}
            </ul>
          </div>
        </div>
      </div>

      {/* Sección 2: Evaluaciones Previas */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 className="text-xl font-bold text-blue-800 mb-4">
          📋 SECCIÓN 2: EVALUACIONES PREVIAS
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <h4 className="font-medium text-gray-800 mb-2">Sensorial:</h4>
            <div className="text-sm text-gray-700">
              {planeacion.evaluaciones.sensorial.length > 0 
                ? planeacion.evaluaciones.sensorial.map(s => s.substring(2)).join(', ')
                : 'Sin sensibilidades reportadas'
              }
            </div>
          </div>
          <div>
            <h4 className="font-medium text-gray-800 mb-2">Habilidades:</h4>
            <div className="text-sm text-gray-700">{planeacion.evaluaciones.habilidades}</div>
          </div>
          <div>
            <h4 className="font-medium text-gray-800 mb-2">Ambiental:</h4>
            <div className="text-sm text-gray-700">
              {planeacion.evaluaciones.ambiental.length > 0
                ? planeacion.evaluaciones.ambiental.map(e => e.substring(2)).join(', ')
                : 'Múltiples entornos'
              }
            </div>
          </div>
        </div>
      </div>

      {/* Sección 3: Implementación */}
      <div className="bg-green-50 border border-green-200 rounded-xl p-6">
        <h3 className="text-xl font-bold text-green-800 mb-4">
          🎯 SECCIÓN 3: IMPLEMENTACIÓN
        </h3>
        <div className="space-y-4">
          <div>
            <h4 className="font-medium text-gray-800 mb-2">Objetivo ND:</h4>
            <p className="text-sm text-gray-700">{planeacion.implementacion.objetivo}</p>
          </div>
          <div>
            <h4 className="font-medium text-gray-800 mb-2">Materiales (con opciones sensoriales):</h4>
            <ul className="text-sm text-gray-700 space-y-1">
              {planeacion.implementacion.materiales.map((material, index) => (
                <li key={index}>🛠️ {material}</li>
              ))}
            </ul>
          </div>
          <div>
            <h4 className="font-medium text-gray-800 mb-2">Tiempo estimado por fase:</h4>
            <p className="text-sm text-gray-700">{planeacion.implementacion.tiempoEstimado}</p>
          </div>
        </div>
      </div>

      {/* Secciones adicionales resumidas */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <h4 className="font-bold text-yellow-800 mb-2">🌍 GENERALIZACIÓN</h4>
          <p className="text-xs text-gray-700">Adaptado para hogar, escuela/trabajo y espacios públicos</p>
        </div>
        <div className="bg-orange-50 border border-orange-200 rounded-lg p-4">
          <h4 className="font-bold text-orange-800 mb-2">👥 CAPACITACIÓN</h4>
          <p className="text-xs text-gray-700">Incluye material para cuidadores y frases clave</p>
        </div>
        <div className="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
          <h4 className="font-bold text-indigo-800 mb-2">📱 TECNOLOGÍA ND</h4>
          <p className="text-xs text-gray-700">Apps recomendadas y prompts IA específicos</p>
        </div>
      </div>

      {/* Cierre Evolutivo ND */}
      <div className="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-xl p-6">
        <h3 className="text-xl font-bold text-purple-800 mb-4">
          🔄 CIERRE EVOLUTIVO ND
        </h3>
        <p className="text-gray-700 mb-4">
          Aquí tienes tu propuesta ND para <strong>{userData.opcionMenu}</strong>. 
          Honra las fortalezas únicas de <strong>{userData.neurodiversidades.join(', ')}</strong> y considera adaptaciones sensoriales.
        </p>
        
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          <button className="p-3 bg-green-100 hover:bg-green-200 border border-green-300 rounded-lg text-sm text-green-800 transition-colors">
            ✅ Implementar tal como está
          </button>
          <button className="p-3 bg-blue-100 hover:bg-blue-200 border border-blue-300 rounded-lg text-sm text-blue-800 transition-colors">
            🧠 Ajustar para otra neurodiversidad
          </button>
          <button className="p-3 bg-purple-100 hover:bg-purple-200 border border-purple-300 rounded-lg text-sm text-purple-800 transition-colors">
            🌍 Expandir a más entornos
          </button>
          <button className="p-3 bg-orange-100 hover:bg-orange-200 border border-orange-300 rounded-lg text-sm text-orange-800 transition-colors">
            👥 Crear material para cuidadores
          </button>
        </div>
      </div>

      <div className="flex justify-between items-center pt-4 border-t">
        <button
          onClick={() => setUserData({tipoUsuario: '', neurodiversidades: [], paso: 1})}
          className="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
        >
          🔄 Nueva planeación
        </button>
        <button
          onClick={() => setUserData({...userData, paso: 6})}
          className="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors"
        >
          ← Cambiar formato
        </button>
      </div>
    </div>
  )
}

// Funciones auxiliares para generar contenido específico
function getFortalezasND(neurodiversidades: string[]): string[] {
  const fortalezas: Record<string, string[]> = {
    tdah: ['Creatividad e innovación', 'Pensamiento divergente', 'Alta energía y entusiasmo'],
    autismo: ['Atención al detalle', 'Pensamiento sistemático', 'Especialización profunda'],
    dislexia: ['Pensamiento visual-espacial', 'Creatividad', 'Habilidades de resolución de problemas'],
    altas_capacidades: ['Procesamiento rápido', 'Conexiones complejas', 'Liderazgo intelectual'],
    procesamiento_sensorial: ['Sensibilidad refinada', 'Percepción detallada', 'Consciencia ambiental']
  }
  
  const resultado: string[] = []
  neurodiversidades.forEach(nd => {
    if (fortalezas[nd]) {
      resultado.push(...fortalezas[nd])
    }
  })
  
  return resultado.length > 0 ? resultado : ['Diversidad de perspectivas', 'Fortalezas únicas individuales', 'Contribución valiosa al grupo']
}

function getObjetivoND(userData: UserData): string {
  if (userData.tipoUsuario === 'docente' && userData.grado && userData.temaDetonador) {
    return `Desarrollar ${userData.temaDetonador} en ${userData.grado} honrando las fortalezas de ${userData.neurodiversidades.join(', ')} con adaptaciones sensoriales y cognitivas apropiadas.`
  }
  return `Crear experiencia inclusiva para ${userData.tipoUsuario} que potencie las fortalezas naturales de ${userData.neurodiversidades.join(', ')}.`
}

function getMaterialesND(userData: UserData): string[] {
  const materiales = [
    'Apoyos visuales con pictogramas y códigos de color',
    'Material sensorial adaptado (texturas, sonidos suaves)',
    'Instrucciones paso a paso con imágenes',
    'Espacios de descanso sensorial',
    'Herramientas de comunicación alternativa'
  ]
  
  if (userData.sensibilidadesSensoriales?.includes('🔊 Sonidos fuertes o inesperados')) {
    materiales.push('Protectores auditivos o música ambiental suave')
  }
  
  if (userData.sensibilidadesSensoriales?.includes('💡 Luces brillantes o parpadeantes')) {
    materiales.push('Iluminación regulable o filtros de luz')
  }
  
  return materiales
}

function getPreparacionEntorno(userData: UserData): string {
  return `Ambiente calmo y predecible, con señalización clara y opciones sensoriales. Considerar ${userData.entornos?.join(', ') || 'múltiples entornos'}.`
}

function getInstruccionesAdaptadas(userData: UserData): string {
  return `Instrucciones multimodales: visual, auditiva y kinestésica. Permitir pausas flexibles y múltiples formas de participación según las necesidades de ${userData.neurodiversidades.join(', ')}.`
}

function getTiempoEstimado(userData: UserData): string {
  if (userData.duracionSesion) {
    return `${userData.duracionSesion} minutos por sesión, distribuidos en fases flexibles con pausas regulares.`
  }
  return 'Tiempo flexible, adaptado al ritmo individual con pausas según necesidad.'
}

function getGeneralizacion(userData: UserData): string {
  return `Actividad adaptable para ${userData.entornos?.join(', ') || 'hogar, escuela y espacios públicos'} con kit portable de herramientas ND.`
}

function getCapacitacion(userData: UserData): string {
  return `Material informativo para ${userData.tipoUsuario === 'padre' ? 'familia extendida' : 'colegas y cuidadores'} sobre fortalezas de ${userData.neurodiversidades.join(', ')} y implementación efectiva.`
}

function getTecnologiaND(userData: UserData): string {
  return `Apps recomendadas para ${userData.neurodiversidades.join(', ')}, prompts IA especializados y herramientas de comunicación aumentativa.`
}

// Añadir el componente del paso 3.5 al flujo principal
export default function AsistenteND() {
  // ... código anterior ...
  
  // En la sección del contenido principal, agregar:
  {userData.paso === 3.5 && <PasoTresYMedio userData={userData} setUserData={setUserData} />}
  
  // ... resto del código ...
}">
            Antes de continuar, ¿hay alguna sensibilidad sensorial que deba considerar?
          </h3>
          <p className="text-sm text-gray-600 mb-4">
            (Sonidos, texturas, luces, etc.) Selecciona todas las que apliquen:
          </p>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
            {sensibilidadesSensoriales.map((sens) => (
              <button
                key={sens}
                onClick={() => toggleSensibilidad(sens)}
                className={`p-3 border-2 rounded-lg transition-all duration-200 text-left text-sm ${
                  sensibilidades.includes(sens)
                    ? 'border-orange-400 bg-orange-50 text-orange-800'
                    : 'border-gray-200 hover:border-orange-300 hover:bg-orange-25'
                }`}
              >
                {sens}
              </button>
            ))}
          </div>
        </div>

        <div>
          <h3 className="text-lg font-medium text-gray-800 mb-3">
            ¿Quieres que incluya una verificación rápida de habilidades antes de la actividad principal?
          </h3>
          <div className="flex gap-4">
            <button
              onClick={() => setVerificacionHabilidades(true)}
              className={`px-6 py-3 border-2 rounded-lg transition-all duration-200 ${
                verificacionHabilidades === true
                  ? 'border-green-400 bg-green-50 text-green-800'
                  : 'border-gray-200 hover:border-green-300'
              }`}
            >
              ✅ Sí, incluir verificación
            </button>
            <button
              onClick={() => setVerificacionHabilidades(false)}
              className={`px-6 py-3 border-2 rounded-lg transition-all duration-200 ${
                verificacionHabilidades === false
                  ? 'border-red-400 bg-red-50 text-red-800'
                  : 'border-gray-200 hover:border-red-300'
              }`}
            >
              ❌ No es necesario
            </button>
          </div>
        </div>
      </div>

      <div className="flex justify-between items-center pt-4 border-t">
        <button
          onClick={() => setUserData({...userData, paso: userData.tipoUsuario === 'docente' && userData.opcionMenu === 'crear' ? 3.5 : 3})}
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
    </div>
  )
}

// Paso 5: Personalización Avanzada
function PasoCinco({ userData, setUserData }: StepProps) {
  const [entornos, setEntornos] = useState<string[]>([])
  const [limitaciones, setLimitaciones] = useState<string[]>([])
  const [prioridad, setPrioridad] = useState('')

  const opcionesEntornos = [
    '🏠 Casa/Hogar',
    '🏫 Escuela/Consultorio', 
    '🌍 Espacios públicos',
    '💼 Trabajo',
    '🚗 Transporte',
    '🛒 Centros comerciales'
  ]

  const opcionesLimitaciones = [
    '⏱️ Limitaciones de tiempo',
    '👥 Otros cuidadores deben implementarla',
    '💰 Pocos recursos materiales',
    '📱 Sin acceso a tecnología',
    '🔇 Entorno ruidoso',
    '👨‍👩‍👧‍👦 Implementación grupal'
  ]

  const toggleEntorno = (entorno: string) => {
    setEntornos(prev => 
      prev.includes(entorno) 
        ? prev.filter(e => e !== entorno)
        : [...prev, entorno]
    )
  }

  const toggleLimitacion = (limitacion: string) => {
    setLimitaciones(prev => 
      prev.includes(limitacion) 
        ? prev.filter(l => l !== limitacion)
        : [...prev, limitacion]
    )
  }

  const continuar = () => {
    setUserData({
      ...userData,
      entornos,
      prioridadUrgente: prioridad,
      paso: 6
    })
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-800 mb-3">
          Personalización Avanzada 🎯
        </h2>
        <p className="text-gray-600 mb-4">
          Para afinar la propuesta. Responde solo lo que sea relevante.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
          <h3 className="text-lg font-medium text-gray-800 mb-3
