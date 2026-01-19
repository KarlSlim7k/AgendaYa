<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Cita</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4F46E5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .info-block { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4F46E5; border-radius: 4px; }
        .info-label { font-weight: bold; color: #4F46E5; }
        .confirmation-code { font-size: 24px; font-weight: bold; color: #4F46E5; text-align: center; padding: 20px; background: white; border-radius: 8px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>¡Cita Confirmada!</h1>
    </div>

    <div class="content">
        <p>Hola <strong>{{ $appointment->user->name }}</strong>,</p>
        
        <p>Tu cita ha sido confirmada exitosamente. A continuación los detalles:</p>

        <div class="info-block">
            <div class="info-label">📍 Negocio</div>
            <div>{{ $appointment->business->nombre }}</div>
        </div>

        <div class="info-block">
            <div class="info-label">🏢 Sucursal</div>
            @if($appointment->businessLocation)
            <div>{{ $appointment->businessLocation->nombre }}</div>
            <div style="color: #6b7280; font-size: 14px;">{{ $appointment->businessLocation->direccion }}</div>
            @else
            <div>No especificada</div>
            @endif
        </div>

        <div class="info-block">
            <div class="info-label">💼 Servicio</div>
            <div>{{ $appointment->service->nombre }}</div>
            <div style="color: #6b7280; font-size: 14px;">Duración: {{ $appointment->service->duracion_minutos }} minutos</div>
        </div>

        <div class="info-block">
            <div class="info-label">👤 Atendido por</div>
            <div>{{ $appointment->employee->nombre }}</div>
        </div>

        <div class="info-block">
            <div class="info-label">📅 Fecha y Hora</div>
            <div>{{ $appointment->fecha_hora_inicio->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</div>
            <div style="font-size: 18px; font-weight: bold; color: #4F46E5; margin-top: 5px;">
                {{ $appointment->fecha_hora_inicio->format('h:i A') }}
            </div>
        </div>

        <div class="confirmation-code">
            <div style="color: #6b7280; font-size: 14px; font-weight: normal;">Código de confirmación</div>
            {{ $appointment->codigo_confirmacion }}
        </div>

        @if($appointment->notas_cliente)
        <div class="info-block">
            <div class="info-label">📝 Notas</div>
            <div>{{ $appointment->notas_cliente }}</div>
        </div>
        @endif

        <p style="margin-top: 30px;">Si necesitas cancelar o reprogramar tu cita, por favor contacta directamente con el negocio.</p>
    </div>

    <div class="footer">
        <p>Este es un correo automático, por favor no responder.</p>
        <p>&copy; {{ date('Y') }} {{ $appointment->business->nombre }}. Todos los derechos reservados.</p>
    </div>
</body>
</html>
