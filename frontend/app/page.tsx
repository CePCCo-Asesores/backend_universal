'use client'

import { useState } from 'react'
import { Brain, Users, BookOpen, Heart, Stethoscope } from 'lucide-react'

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

export default function AsistenteND() {
  const [userData, setUserData] = useState<UserData>({
    tipoUsuario: '',
    neurodiversidades: [],
    paso: 1
  })

  const [historial, setHistorial] = useState<string[]>([])

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 via-blue-50 to-indigo-50 p-4">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <header className="text-center mb-8">
          <Brain className="w-16 h-16 mx-auto text-purple-600 mb-4" />
          <h1 className="text-4xl font-bold text-gray-800 mb-2">
            🧠 Asistente de Planeación Inclusiva y Neurodivergente
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
function PasoUno({ userData, setUserData }: any) {
  const tiposUsuario = [
    { id: 'docente', label: '1️⃣ Docente', icon: BookOpen, descripcion: 'Trabajo con estudiantes en aula' },
    { id: 'terapeuta', label: '2️⃣ Terapeuta', icon: Heart, descripcion: 'Trabajo con clientes/pacientes en sesiones' },
    { id: 'padre', label: '3️⃣ Padre/Madre', icon: Users, descripcion: 'Trabajo con mis hijos en rutinas familiares' },
    { id: 'medico', label: '4️⃣ Médico', icon: Stethoscope, descripcion: 'Trabajo con pacientes en consultorio' },
    { id: 'mixto', label: '6️⃣ Mixto', icon: Users, descripcion: 'Ej. madre-docente, terapeuta-padre' }
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
            <tipo.icon className="w-8 h-8 mr-4 text-purple-600 group-hover:text-purple-700 flex-shrink-0 mt-1" />
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
function PasoDos({ userData, setUserData }: any) {
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

// Placeholder para los demás pasos
function PasoTres({ userData, setUserData }: any) {
  return (
    <div className="text-center py-12">
      <h2 className="text-2xl font-bold mb-4">Paso 3: Menú Contextualizado</h2>
      <p className="text-gray-600 mb-6">En desarrollo...</p>
      <button
        onClick={() => setUserData({...userData, paso: 2})}
        className="px-6 py-2 text-gray-600 hover:text-gray-800"
      >
        ← Volver al paso anterior
      </button>
    </div>
  )
}

function PasoCuatro({ userData, setUserData }: any) {
  return (
    <div className="text-center py-12">
      <h2 className="text-2xl font-bold mb-4">Paso 4: Recolección Inteligente</h2>
      <p className="text-gray-600 mb-6">En desarrollo...</p>
      <button
        onClick={() => setUserData({...userData, paso: 3})}
        className="px-6 py-2 text-gray-600 hover:text-gray-800"
      >
        ← Volver al paso anterior
      </button>
    </div>
  )
}

function PasoCinco({ userData, setUserData }: any) {
  return (
    <div className="text-center py-12">
      <h2 className="text-2xl font-bold mb-4">Paso 5: Personalización Avanzada</h2>
      <p className="text-gray-600 mb-6">En desarrollo...</p>
      <button
        onClick={() => setUserData({...userData, paso: 4})}
        className="px-6 py-2 text-gray-600 hover:text-gray-800"
      >
        ← Volver al paso anterior
      </button>
    </div>
  )
}

function PasoSeis({ userData, setUserData }: any) {
  return (
    <div className="text-center py-12">
      <h2 className="text-2xl font-bold mb-4">Paso 6: Generación de Planeación ND</h2>
      <p className="text-gray-600 mb-6">En desarrollo...</p>
      <button
        onClick={() => setUserData({...userData, paso: 5})}
        className="px-6 py-2 text-gray-600 hover:text-gray-800"
      >
        ← Volver al paso anterior
      </button>
    </div>
  )
}
