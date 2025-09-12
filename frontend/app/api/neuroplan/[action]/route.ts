import { NextResponse } from 'next/server'
import { cookies } from 'next/headers'

const BACKEND_URL = process.env.BACKEND_URL // ej: https://tu-backend.up.railway.app

export async function POST(req: Request, { params }: { params: { action: string } }) {
  if (!BACKEND_URL) {
    return NextResponse.json({ error: 'BACKEND_URL no configurado' }, { status: 500 })
  }

  const { action } = params // "start" | "step" | "generate"
  if (!['start', 'step', 'generate'].includes(action)) {
    return NextResponse.json({ error: 'action inválida' }, { status: 400 })
  }

  const bodyFromClient = await req.json().catch(() => ({}))
  const jwt = cookies().get('jwt')?.value

  const upstream = await fetch(`${BACKEND_URL}/module/activate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(jwt ? { Authorization: `Bearer ${jwt}` } : {})
    },
    body: JSON.stringify({
      module: 'NEUROPLAN_360',
      payload: { action, ...bodyFromClient }
    }),
    cache: 'no-store'
  })

  const data = await upstream.json().catch(() => ({}))
  return NextResponse.json(data, { status: upstream.status })
}
