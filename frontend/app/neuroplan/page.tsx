'use client'

import { useState } from 'react'

export default function NeuroplanPage() {
  const [sessionId, setSessionId] = useState('')
  const [step, setStep] = useState(0)
  const [plan, setPlan] = useState<any>(null)

  const start = async () => {
    const r = await fetch('/api/neuroplan/start', { method: 'POST' })
    const j = await r.json()
    setSessionId(j.session_id)
    setStep(j.next_step)
  }

  const sendStep = async () => {
    if (!sessionId) return
    let input: Record<string, any> = {}
    if (step === 1) input = { tipoUsuario: 'docente' }
    if (step === 2) input = { neurodiversidades: ['autismo'] }
    if (step === 3) input = { opcionMenu: 'crear' }
    if (step === 35) input = { contexto: { grado: '3°', contenido: 'Matemáticas', tema: 'Fracciones', sesiones: 4, duracion: 50 } }
    if (step === 4) input = { sensibilidades: [] }
    if (step === 5) input = { entornos: ['🏫 Escuela/Consultorio'], limitaciones: [], prioridad: '' }
    if (step === 6) input = { formato: 'practico' }

    const r = await fetch('/api/neuroplan/step', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: sessionId, step, input })
    })
    const j = await r.json()
    setStep(j.next_step ?? step)
  }

  const generate = async () => {
    const r = await fetch('/api/neuroplan/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_id: sessionId })
    })
    const j = await r.json()
    setPlan(j.plan)
  }

  return (
    <div className="max-w-xl mx-auto p-6 space-y-3">
      <h1 className="text-2xl font-bold">Neuroplan 360</h1>

      {!sessionId && (
        <button onClick={start} className="px-4 py-2 bg-purple-600 text-white rounded">Iniciar</button>
      )}

      {sessionId && !plan && (
        <div className="space-y-2">
          <div className="text-sm">Session: <code>{sessionId}</code></div>
          <div className="text-sm">Paso actual: <strong>{step}</strong></div>
          {step >= 1 && step <= 6 ? (
            <button onClick={sendStep} className="px-4 py-2 bg-blue-600 text-white rounded">
              Enviar paso {step}
            </button>
          ) : (
            <button onClick={generate} className="px-4 py-2 bg-green-600 text-white rounded">
              Generar plan
            </button>
          )}
        </div>
      )}

      {plan && (
        <>
          <h2 className="text-xl font-semibold">Plan generado</h2>
          <pre className="bg-gray-100 p-3 rounded text-xs overflow-auto">{JSON.stringify(plan, null, 2)}</pre>
        </>
      )}
    </div>
  )
}
