import { NextResponse } from 'next/server'

const BACKEND_URL = process.env.BACKEND_URL // ej: https://tu-backend.up.railway.app

export async function POST(req: Request) {
  if (!BACKEND_URL) {
    return NextResponse.json({ error: 'BACKEND_URL no configurado' }, { status: 500 })
  }

  const body = await req.json().catch(() => ({}))

  const upstream = await fetch(`${BACKEND_URL}/auth/google`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
    cache: 'no-store'
  })

  const data = await upstream.json().catch(() => ({}))
  const token = (data && (data.token || data.jwt)) as string | undefined

  const res = NextResponse.json(data, { status: upstream.status })

  if (token) {
    res.cookies.set({
      name: 'jwt',
      value: token,
      httpOnly: true,
      secure: true,
      sameSite: 'lax',
      path: '/',
      maxAge: 60 * 60 * 24 * 14
    })
  }

  return res
}
