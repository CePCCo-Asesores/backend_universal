'use client'

import { useState } from 'react'

export default function NeuroplanPage() {
  const [sessionId, setSessionId] = useState('')
  const [step, setStep] = useState(0)
  const [plan, setPlan] = useState<any>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const start = async () => {
    setLoading(true); setError(null)
    try {
      const r = await fetch('/api/neuroplan/start', { method: 'POST' })
      const j = await r.json()
      if (!r.ok) throw new Error(j?.error || 'No se pudo iniciar')
      setSessionId(j.session_id)
      setStep(j.next_step)
    } catch (e: any) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }

  const sendStep = async () => {
    if (!sessionId) return
    setLoading(true); setError(null)
    try {
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
      if (!r.ok) throw new Error(j?.error || 'Error al guardar paso')
      setStep(j.next_step ?? step)
    } catch (e: any) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }

  const generate = async () => {
    if (!sessionId) return
    setLoading(true); setError(null)
    try {
      const r = await fetch('/api/neuroplan/generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sessionId })
      })
      const j = await r.json()
      if (!r.ok) throw new Error(j?.error || 'Error al generar plan')
      setPlan(j.plan)
    } catch (e: any) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="max-w-xl mx-auto p-6 space-y-4">
      <h1 className="text-2xl font-bold">Neuroplan 360</h1>

      {error && <div className="p-3 rounded bg-red-100 text-red-800 text-sm">{error}</div>}

      {!sessionId && (
        <button onClick={start} className="px-4 py-2 bg-purple-600 text-white rounded disabled:opacity-50" disabled={loading}>
          {loading ? 'Iniciando…' : 'Iniciar'}
        </button>
      )}

      {sessionId && !plan && (
        <div className="space-y-2">
          <div className="text-sm">Session: <code>{sessionId}</code></div>
          <div className="text-sm">Paso actual: <strong>{step}</strong></div>
          {step >= 1 && step <= 6 ? (
            <button onClick={sendStep} className="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50" disabled={loading}>
              {loading ? 'Enviando…' : `Enviar paso ${step}`}
            </button>
          ) : (
            <button onClick={generate} className="px-4 py-2 bg-green-600 text-white rounded disabled:opacity-50" disabled={loading}>
              {loading ? 'Generando…' : 'Generar plan'}
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
    </main>
  )
}
