'use client'

import { useState } from 'react'
import { useRouter, useSearchParams } from 'next/navigation'

export default function LoginPage() {
  const [token, setToken] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const router = useRouter()
  const sp = useSearchParams()
  const redirect = sp.get('redirect') || '/neuroplan'

  const submit = async () => {
    setLoading(true); setError(null)
    try {
      const r = await fetch('/api/auth/google', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token_google: token })
      })
      const j = await r.json()
      if (!r.ok) throw new Error(j?.error || 'No se pudo autenticar')
      router.push(redirect)
    } catch (e: any) {
      setError(e.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="max-w-md mx-auto p-6 space-y-4">
      <h1 className="text-2xl font-bold">Login</h1>
      <p className="text-sm text-gray-600">Pega aquí un <strong>ID token</strong> de Google para pruebas.</p>

      {error && <div className="p-3 rounded bg-red-100 text-red-800 text-sm">{error}</div>}

      <textarea
        className="w-full h-32 p-3 border rounded"
        placeholder="ID token de Google"
        value={token}
        onChange={(e) => setToken(e.target.value)}
      />
      <button onClick={submit} className="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50" disabled={loading || !token.trim()}>
        {loading ? 'Autenticando…' : 'Entrar'}
      </button>
    </main>
  )
}
