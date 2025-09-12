'use client';

import { useSearchParams } from 'next/navigation';
import { Suspense } from 'react';

// Componente que usa useSearchParams
function LoginForm() {
  const searchParams = useSearchParams();
  
  // Tu lógica del componente login aquí
  const error = searchParams.get('error');
  const callbackUrl = searchParams.get('callbackUrl');
  
  return (
    <div>
      {error && (
        <div className="error-message">
          Error: {error}
        </div>
      )}
      
      {/* Tu formulario de login */}
      <form>
        {/* Tus campos de login */}
        <input type="email" placeholder="Email" />
        <input type="password" placeholder="Password" />
        <button type="submit">Iniciar Sesión</button>
      </form>
      
      {callbackUrl && (
        <input type="hidden" name="callbackUrl" value={callbackUrl} />
      )}
    </div>
  );
}

// Página principal
export default function LoginPage() {
  return (
    <div className="login-container">
      <h1>Iniciar Sesión</h1>
      
      <Suspense fallback={<div>Cargando...</div>}>
        <LoginForm />
      </Suspense>
    </div>
  );
}
