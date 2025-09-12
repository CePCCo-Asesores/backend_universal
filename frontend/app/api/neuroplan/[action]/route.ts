import { NextResponse, type NextRequest } from 'next/server'

const BACKEND_URL = process.env.BACKEND_URL // ej: https://cepcco-backend-production.up.railway.app

export async function POST(
  req: NextRequest,
  context: { params?: Record<string, string | string[]> }
) {
  if (!BACKEND_URL) {
    return NextResponse.json({ error: 'BACKEND_URL no configurado' }, { status: 500 })
  }

  const actionParam = context?.params?.action
  const action = Array.isArray(actionParam) ? actionParam[0] : actionParam

  if (!action || !['start', 'step', 'generate'].includes(action)) {
    return NextResponse.json({ error: 'action inválida' }, { status: 400 })
  }

  const bodyFromClient = await req.json().catch(() => ({} as Record<string, unknown>))
  const jwt = req.cookies.get('jwt')?.value

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
