// Opción 2: Versión simplificada sin parámetros dinámicos
export async function POST(request: NextRequest): Promise<NextResponse> {
  try {
    const body = await request.json();
    
    // Tu lógica aquí
    return NextResponse.json({ 
      success: true,
      data: body,
      message: 'Procesado exitosamente' 
    });
    
  } catch (error) {
    console.error('Error:', error);
    return NextResponse.json(
      { error: 'Error procesando la solicitud' },
      { status: 500 }
    );
  }
}

export async function GET(request: NextRequest): Promise<NextResponse> {
  try {
    return NextResponse.json({ 
      message: 'API funcionando correctamente' 
    });
  } catch (error) {
    return NextResponse.json(
      { error: 'Error en GET request' },
      { status: 500 }
    );
  }
}
