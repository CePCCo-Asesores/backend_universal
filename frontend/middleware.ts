import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

export function middleware(req: NextRequest) {
  const { pathname } = req.nextUrl
  if (pathname.startsWith('/neuroplan')) {
    const hasJWT = req.cookies.get('jwt')?.value
    if (!hasJWT) {
      const url = req.nextUrl.clone()
      url.pathname = '/login'
      url.searchParams.set('redirect', pathname)
      return NextResponse.redirect(url)
    }
  }
  return NextResponse.next()
}

export const config = { matcher: ['/neuroplan/:path*'] }

