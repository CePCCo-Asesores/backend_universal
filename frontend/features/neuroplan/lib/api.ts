export async function neuroplanStart() {
  const r = await fetch('/api/neuroplan/start', { method: 'POST' })
  if (!r.ok) throw new Error('No se pudo iniciar')
  return r.json()
}
export async function neuroplanStep(session_id: string, step: number, input: Record<string, any>) {
  const r = await fetch('/api/neuroplan/step', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ session_id, step, input })
  })
  if (!r.ok) throw new Error('Error al guardar paso')
  return r.json()
}
export async function neuroplanGenerate(session_id: string) {
  const r = await fetch('/api/neuroplan/generate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ session_id })
  })
  if (!r.ok) throw new Error('Error al generar plan')
  return r.json()
}
