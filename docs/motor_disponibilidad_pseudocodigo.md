# Motor de Disponibilidad - Pseudocódigo y Lógica

## Introducción

El motor de disponibilidad es el corazón del sistema de reservas. Su responsabilidad es:
1. **Generar slots disponibles** para una solicitud de disponibilidad (GET /availability/slots)
2. **Validar reservas** antes de crear una cita (POST /appointments)

Debe garantizar:
- No hay solapamiento de citas en el mismo empleado/recurso
- Se respeta el horario laboral de la sucursal
- Se respetan excepciones (vacaciones, días festivos, cierres)
- Se aplican buffers pre y post-cita
- Se respeta la duración del servicio

---

## Reglas de Disponibilidad

### R1: Horario Base
La cita debe empezar Y terminar dentro del horario laboral de la sucursal para ese día de la semana.

**Ejemplo:**
- Sucursal abre 09:00 - 18:00
- Servicio dura 30 minutos
- Slots válidos: 09:00-09:30, 09:15-09:45, ..., 17:30-18:00
- Slots inválidos: 17:45-18:15 (termina fuera de horario)

### R2: Excepciones (Vacaciones, Festivos, Cierres)
No se puede agendar en fechas/horas marcadas como excepción.

**Campos en tabla `schedule_exceptions`:**
- `fecha`: Fecha específica
- `tipo`: 'feriado', 'vacaciones', 'cierre'
- `todo_el_dia`: bool (si es true, excluir todo el día)
- `hora_inicio`, `hora_fin`: Si todo_el_dia=false, solo este rango

### R3: Bloqueo Empleado/Recurso
Un empleado o recurso NO puede tener dos citas que se solapen en tiempo.

**Solapamiento:** 
```
Cita A: [10:00 - 10:30]
Cita B: [10:15 - 10:45]  ← Solapan

Cita C: [10:30 - 11:00]  ← NO solapan (sin buffer)
```

### R4: Duración del Servicio
El slot debe tener duración >= duración del servicio.

**Ejemplo:**
- Servicio "Corte" dura 30 minutos
- Slot: 09:00 - 09:30 ✓
- Slot: 09:00 - 09:15 ✗ (muy corto)

### R5: Buffer Post-Cita
Tiempo muerto DESPUÉS de una cita para limpieza/preparación.

**Ejemplo:**
- Cita 1: 09:00 - 09:30
- Buffer post: 15 minutos
- Siguiente cita NO puede empezar antes de 09:45

### R6: Buffer Pre-Cita
Tiempo muerto ANTES de una cita para preparación.

**Ejemplo:**
- Buffer pre: 10 minutos
- Siguiente cita: 10:00
- Cita anterior debe terminar antes de 09:50

### R7: Capacidad de Recursos (Fase 2)
Algunos recursos (ej: sala) tienen capacidad limitada.

**Ejemplo:**
- Sala "A" tiene capacidad 3
- Si hay 3 citas simultáneas, NO se puede agendar una 4ta

---

## Pseudocódigo: Generar Slots Disponibles

### Función Principal
```
FUNCIÓN generarSlotsDisponibles(
    business_id,
    service_id,
    location_id,
    fecha_inicio,
    fecha_fin,
    employee_id = NULL,
    resource_id = NULL
) RETORNA Lista<Slot>

    // PASO 1: Validar input
    SI fecha_fin < fecha_inicio ENTONCES
        LANZAR_ERROR("Fecha fin debe ser mayor a fecha inicio")
    FIN SI

    slots_disponibles = Lista vacía

    // PASO 2: Iterar cada día en el rango
    PARA cada_dia DESDE fecha_inicio HASTA fecha_fin HACER
        
        // Obtener horario base de la sucursal para este día
        dia_semana = obtenerDiaSemana(cada_dia)  // 0=Lunes, 6=Domingo
        horario_base = obtenerScheduleTemplate(location_id, dia_semana)
        
        SI horario_base ES NULL ENTONCES
            CONTINUAR  // No hay horario configurado
        FIN SI

        // PASO 3: Validar excepciones (vacaciones, festivos, etc.)
        SI existeExcepcion(location_id, cada_dia) ENTONCES
            excepcion = obtenerExcepcion(location_id, cada_dia)
            
            SI excepcion.todo_el_dia ENTONCES
                CONTINUAR  // Día completamente bloqueado
            FIN SI
            
            // Si es una excepción parcial, ajustar horario_base
            horario_base = ajustarHorarioConExcepcion(horario_base, excepcion)
        FIN SI

        // PASO 4: Generar slots base para el día (sin validar citas)
        slots_dia = generarSlotsDelDia(
            horario_base,
            cada_dia,
            duracion_servicio,
            intervalo_minutos = 15  // Intervalo entre slots (ej: cada 15 min)
        )

        // PASO 5: Si se especificó empleado, filtrar su disponibilidad
        SI employee_id NO ES NULL ENTONCES
            slots_dia = filtrarSlotsEmpleado(
                location_id,
                employee_id,
                slots_dia
            )
        FIN SI

        // PASO 6: Si se especificó recurso, filtrar su disponibilidad
        SI resource_id NO ES NULL ENTONCES
            slots_dia = filtrarSlotsRecurso(
                location_id,
                resource_id,
                slots_dia
            )
        FIN SI

        // PASO 7: Si NO se especificó empleado, devolver slots genéricos
        SI employee_id ES NULL ENTONCES
            SI resource_id ES NULL ENTONCES
                // Devolver slots sin asignación específica
                // Frontend mostrará "empleado disponible" o "cualquier empleado"
                PARA cada_slot EN slots_dia HACER
                    cada_slot.available_employees = obtenerEmpleadosDisponibles(
                        location_id,
                        service_id,
                        cada_slot.hora_inicio,
                        cada_slot.hora_fin
                    )
                FIN PARA
            FIN SI
        FIN SI

        // PASO 8: Agregar slots válidos al resultado
        slots_disponibles.agregarTodos(slots_dia)

    FIN PARA

    RETORNAR slots_disponibles

FIN FUNCIÓN
```

### Función Auxiliar: Generar Slots Base del Día
```
FUNCIÓN generarSlotsDelDia(
    horario_base,
    fecha,
    duracion_servicio_minutos,
    intervalo_minutos = 15
) RETORNA Lista<Slot>

    slots = Lista vacía
    
    hora_actual = horario_base.hora_apertura
    hora_cierre = horario_base.hora_cierre

    MIENTRAS hora_actual + duracion_servicio_minutos <= hora_cierre HACER
        
        slot_inicio = hora_actual
        slot_fin = hora_actual + duracion_servicio_minutos
        
        slot_nuevo = {
            fecha: fecha,
            hora_inicio: slot_inicio,
            hora_fin: slot_fin,
            disponible: VERDADERO
        }
        
        slots.agregar(slot_nuevo)
        
        hora_actual = hora_actual + intervalo_minutos

    FIN MIENTRAS

    RETORNAR slots

FIN FUNCIÓN
```

### Función Auxiliar: Filtrar Slots por Empleado
```
FUNCIÓN filtrarSlotsEmpleado(
    location_id,
    employee_id,
    slots_dia
) RETORNA Lista<Slot>

    slots_validos = Lista vacía

    PARA cada_slot EN slots_dia HACER
        
        // PASO 1: Obtener todas las citas del empleado en este día
        citas_empleado = obtenerCitasEmpleado(
            employee_id,
            location_id,
            fecha = cada_slot.fecha,
            estado IN ('confirmed', 'completed')  // No contar canceladas
        )

        // PASO 2: Verificar si hay solapamiento considerando buffers
        hay_solapamiento = FALSO

        PARA cada_cita EN citas_empleado HACER
            
            // Expandir rangos de cita con buffers
            cita_inicio_con_buffer = cada_cita.hora_inicio - buffer_pre_minutos
            cita_fin_con_buffer = cada_cita.hora_fin + buffer_post_minutos
            
            slot_inicio_con_buffer = cada_slot.hora_inicio - buffer_pre_minutos
            slot_fin_con_buffer = cada_slot.hora_fin + buffer_post_minutos

            // Verificar solapamiento
            SI solapan(
                (cita_inicio_con_buffer, cita_fin_con_buffer),
                (slot_inicio_con_buffer, slot_fin_con_buffer)
            ) ENTONCES
                hay_solapamiento = VERDADERO
                ROMPER
            FIN SI

        FIN PARA

        // PASO 3: Si no hay solapamiento, es un slot válido
        SI NO hay_solapamiento ENTONCES
            cada_slot.employee_id = employee_id
            slots_validos.agregar(cada_slot)
        FIN SI

    FIN PARA

    RETORNAR slots_validos

FIN FUNCIÓN
```

### Función Auxiliar: Filtrar Slots por Recurso
```
FUNCIÓN filtrarSlotsRecurso(
    location_id,
    resource_id,
    slots_dia
) RETORNA Lista<Slot>

    slots_validos = Lista vacía
    recurso = obtenerRecurso(resource_id)

    PARA cada_slot EN slots_dia HACER
        
        // PASO 1: Obtener citas que usan este recurso en el slot
        citas_recurso = obtenerCitasRecurso(
            resource_id,
            location_id,
            fecha = cada_slot.fecha,
            estado IN ('confirmed', 'completed')
        )

        // PASO 2: Contar cuántas citas solapan con el slot
        citas_solapadas = 0

        PARA cada_cita EN citas_recurso HACER
            
            // Expandir con buffers
            cita_inicio_buffer = cada_cita.hora_inicio - buffer_pre_minutos
            cita_fin_buffer = cada_cita.hora_fin + buffer_post_minutos
            
            slot_inicio_buffer = cada_slot.hora_inicio - buffer_pre_minutos
            slot_fin_buffer = cada_slot.hora_fin + buffer_post_minutos

            SI solapan(
                (cita_inicio_buffer, cita_fin_buffer),
                (slot_inicio_buffer, slot_fin_buffer)
            ) ENTONCES
                citas_solapadas = citas_solapadas + 1
            FIN SI

        FIN PARA

        // PASO 3: Verificar capacidad del recurso
        SI citas_solapadas < recurso.capacidad ENTONCES
            cada_slot.resource_id = resource_id
            slots_validos.agregar(cada_slot)
        FIN SI

    FIN PARA

    RETORNAR slots_validos

FIN FUNCIÓN
```

### Función Auxiliar: Detectar Solapamiento
```
FUNCIÓN solapan(rango1, rango2) RETORNA BOOLEANO

    // rango1 = (inicio1, fin1)
    // rango2 = (inicio2, fin2)
    // Dos rangos solapan si:
    // inicio1 < fin2 AND inicio2 < fin1

    RETORNAR rango1.inicio < rango2.fin Y rango2.inicio < rango1.fin

FIN FUNCIÓN
```

---

## Pseudocódigo: Validar Reserva (POST /appointments)

```
FUNCIÓN validarYCrearCita(
    business_id,
    user_id,
    service_id,
    location_id,
    employee_id,  // Puede ser NULL si user eligió "cualquier empleado"
    fecha_hora_inicio,
    fecha_hora_fin,
    client_notes = NULL
) RETORNA Appointment O ERROR

    // PASO 1: Validaciones básicas
    SI fecha_hora_inicio >= fecha_hora_fin ENTONCES
        LANZAR_ERROR("Hora de fin debe ser posterior a hora de inicio")
    FIN SI

    SI fecha_hora_inicio < AHORA() ENTONCES
        LANZAR_ERROR("No se puede agendar en el pasado")
    FIN SI

    // PASO 2: Obtener entidades
    servicio = obtenerServicio(service_id, business_id)
    sucursal = obtenerBusinessLocation(location_id, business_id)
    
    SI servicio ES NULL O sucursal ES NULL ENTONCES
        LANZAR_ERROR("Servicio o sucursal inválidos")
    FIN SI

    // PASO 3: Validar duración del servicio
    duracion_solicitada = fecha_hora_fin - fecha_hora_inicio
    SI duracion_solicitada != servicio.duracion_minutos ENTONCES
        LANZAR_ERROR("Duración de la cita no coincide con el servicio")
    FIN SI

    // PASO 4: Validar horario base
    dia_semana = obtenerDiaSemana(fecha_hora_inicio)
    horario_base = obtenerScheduleTemplate(location_id, dia_semana)
    
    SI horario_base ES NULL ENTONCES
        LANZAR_ERROR("No hay horario configurado para este día")
    FIN SI

    SI fecha_hora_inicio < horario_base.hora_apertura O 
       fecha_hora_fin > horario_base.hora_cierre ENTONCES
        LANZAR_ERROR("Cita fuera del horario de la sucursal")
    FIN SI

    // PASO 5: Validar excepciones
    SI existeExcepcion(location_id, fecha = fecha_hora_inicio.fecha) ENTONCES
        excepcion = obtenerExcepcion(location_id, fecha = fecha_hora_inicio.fecha)
        
        SI excepcion.todo_el_dia ENTONCES
            LANZAR_ERROR("Sucursal cerrada en esta fecha")
        FIN SI
        
        SI solapan(
            (excepcion.hora_inicio, excepcion.hora_fin),
            (fecha_hora_inicio.hora, fecha_hora_fin.hora)
        ) ENTONCES
            LANZAR_ERROR("Cita en horario cerrado por excepción")
        FIN SI
    FIN SI

    // PASO 6: Assignar empleado si fue NULL (cualquier empleado)
    SI employee_id ES NULL ENTONCES
        empleados_disponibles = obtenerEmpleadosDisponibles(
            location_id,
            service_id,
            fecha_hora_inicio,
            fecha_hora_fin
        )
        
        SI empleados_disponibles.es_vacia() ENTONCES
            LANZAR_ERROR("No hay empleados disponibles")
        FIN SI
        
        employee_id = empleados_disponibles[0].id  // Asignar el primero
    FIN SI

    // PASO 7: TRANSACCIÓN - Validación y creación con lock pessimista
    INICIAR_TRANSACCION()
    
    INTENTAR
        
        // Lock pessimista: SELECT FOR UPDATE en la agenda del empleado
        // para evitar doble booking en caso de concurrencia
        citas_solapadas = OBTENER_CON_LOCK(
            query = "SELECT * FROM appointments 
                     WHERE business_id = ? 
                       AND location_id = ? 
                       AND employee_id = ? 
                       AND fecha >= fecha_inicio_busqueda
                       AND fecha <= fecha_fin_busqueda
                     FOR UPDATE",
            params = (business_id, location_id, employee_id),
            fecha_inicio_busqueda = fecha_hora_inicio - 1 hora,
            fecha_fin_busqueda = fecha_hora_fin + 1 hora
        )

        // PASO 8: Verificar solapamiento final
        PARA cada_cita_existente EN citas_solapadas HACER
            
            cita_inicio_buffer = cada_cita_existente.hora_inicio - servicio.buffer_pre_minutos
            cita_fin_buffer = cada_cita_existente.hora_fin + servicio.buffer_post_minutos
            
            slot_inicio_buffer = fecha_hora_inicio - servicio.buffer_pre_minutos
            slot_fin_buffer = fecha_hora_fin + servicio.buffer_post_minutos

            SI solapan(
                (cita_inicio_buffer, cita_fin_buffer),
                (slot_inicio_buffer, slot_fin_buffer)
            ) ENTONCES
                ROMPER_TRANSACCION()
                LANZAR_ERROR("Slot ya no disponible (otra reserva simultánea)")
            FIN SI

        FIN PARA

        // PASO 9: Crear la cita
        cita_nueva = {
            id: generar_uuid(),
            business_id: business_id,
            user_id: user_id,
            location_id: location_id,
            service_id: service_id,
            employee_id: employee_id,
            fecha_hora_inicio: fecha_hora_inicio,
            fecha_hora_fin: fecha_hora_fin,
            estado: 'confirmed',
            client_notes: client_notes,
            created_at: AHORA(),
            updated_at: AHORA()
        }

        guardarEnBD(cita_nueva)

        // PASO 10: Crear registro de historial
        historial = {
            appointment_id: cita_nueva.id,
            estado_anterior: NULL,
            estado_nuevo: 'confirmed',
            cambiado_por: user_id,
            fecha_cambio: AHORA()
        }
        guardarEnBD(historial)

        CONFIRMAR_TRANSACCION()

        RETORNAR cita_nueva

    CAPTURAR error ENTONCES
        DESHACER_TRANSACCION()
        LANZAR_ERROR(error.mensaje)
    FIN CAPTURAR

FIN FUNCIÓN
```

---

## Función Auxiliar: Obtener Empleados Disponibles

```
FUNCIÓN obtenerEmpleadosDisponibles(
    location_id,
    service_id,
    fecha_hora_inicio,
    fecha_hora_fin
) RETORNA Lista<Employee>

    // PASO 1: Obtener empleados que dan este servicio en esta sucursal
    empleados_servicio = obtenerEmpleadosConServicio(
        location_id,
        service_id
    )

    SI empleados_servicio.es_vacia() ENTONCES
        RETORNAR Lista vacía
    FIN SI

    // PASO 2: Filtrar disponibles en el rango horario
    empleados_disponibles = Lista vacía

    PARA cada_empleado EN empleados_servicio HACER
        
        citas_conflicto = obtenerCitasEmpleado(
            employee_id = cada_empleado.id,
            location_id = location_id,
            fecha = fecha_hora_inicio.fecha,
            estado IN ('confirmed', 'completed')
        )

        hay_conflicto = FALSO

        PARA cada_cita EN citas_conflicto HACER
            
            cita_inicio_buffer = cada_cita.hora_inicio - buffer_pre_minutos
            cita_fin_buffer = cada_cita.hora_fin + buffer_post_minutos
            
            slot_inicio_buffer = fecha_hora_inicio - buffer_pre_minutos
            slot_fin_buffer = fecha_hora_fin + buffer_post_minutos

            SI solapan(
                (cita_inicio_buffer, cita_fin_buffer),
                (slot_inicio_buffer, slot_fin_buffer)
            ) ENTONCES
                hay_conflicto = VERDADERO
                ROMPER
            FIN SI

        FIN PARA

        SI NO hay_conflicto ENTONCES
            empleados_disponibles.agregar(cada_empleado)
        FIN SI

    FIN PARA

    RETORNAR empleados_disponibles

FIN FUNCIÓN
```

---

## Casos de Uso y Ejemplos de Ejecución

### Caso 1: Generación de Slots Simple
```
ENTRADA:
  - business_id = 1
  - service_id = 5 (Corte de cabello, 30 min)
  - location_id = 1 (Peluquería "La Moderna")
  - fecha_inicio = 2024-01-15 (lunes)
  - fecha_fin = 2024-01-15
  - employee_id = NULL (cualquier empleado)

DATOS EN BD:
  - Horario lunes: 09:00 - 18:00
  - Empleado A: cita 10:00-10:30, cita 11:00-11:30
  - Empleado B: cita 09:00-09:30
  - Buffer pre/post = 0 minutos
  - Intervalo = 15 minutos

EJECUCIÓN:
  1. Generar slots base: [09:00-09:30], [09:15-09:45], ..., [17:30-18:00]
  2. Filtrar por Empleado A:
     - [09:00-09:30] → Conflicto (cita existente)
     - [09:15-09:45] → Conflicto (solapan)
     - [09:30-10:00] → OK
     - [09:45-10:15] → Conflicto (cita siguiente)
     - [10:30-11:00] → Conflicto (cita siguiente)
     - [11:30-12:00] → OK
     - ...
  3. Filtrar por Empleado B:
     - [09:00-09:30] → Conflicto (cita existente)
     - [09:30-10:00] → OK
     - ...
  4. Disponibilidad final (mostrar "cualquier empleado"):
     - [09:30-10:00] → Empleado A y B disponibles
     - [10:30-11:00] → Solo Empleado B disponible
     - ...

SALIDA:
  {
    "slots": [
      {
        "fecha": "2024-01-15",
        "hora_inicio": "09:30",
        "hora_fin": "10:00",
        "available_employees": [
          {"id": 1, "name": "Juan"},
          {"id": 2, "name": "Carlos"}
        ]
      },
      ...
    ]
  }
```

### Caso 2: Validación de Reserva con Concurrencia
```
ENTRADA (Usuario 1):
  - Elige slot 10:00-10:30 con Empleado A
  - POST /appointments
  - Transacción abierta, lock pessimista adquirido

ENTRADA (Usuario 2, simultáneamente):
  - Intenta el MISMO slot 10:00-10:30 con Empleado A
  - POST /appointments
  - Espera por el lock (usuario 1 lo tiene)

EJECUCIÓN:
  1. Usuario 1: Lock adquirido, verifica citas_solapadas (ninguna)
  2. Usuario 1: Crea cita, confirma transacción, libera lock
  3. Usuario 2: Adquiere lock, verifica citas_solapadas (¡encuentra la de usuario 1!)
  4. Usuario 2: Error "Slot ya no disponible"

SALIDA Usuario 2:
  {
    "error": "Slot ya no disponible (otra reserva simultánea)",
    "code": "SLOT_UNAVAILABLE"
  }
```

### Caso 3: Buffer Pre y Post
```
DATOS:
  - Servicio "Masaje" dura 60 minutos
  - Buffer pre = 10 minutos (preparación)
  - Buffer post = 15 minutos (limpieza)
  - Cita existente: 14:00 - 15:00

RANGO BLOQUEADO CON BUFFERS:
  - Inicio: 14:00 - 10 min = 13:50
  - Fin: 15:00 + 15 min = 15:15

SLOTS INVÁLIDOS:
  - [13:50-14:50] → Solapan (comienzan antes de 15:15)
  - [13:55-14:55] → Solapan
  - [15:00-16:00] → Solapan (comienzan antes de 15:15)
  - [15:10-16:10] → Solapan (comienzan antes de 15:15)

PRIMER SLOT VÁLIDO:
  - [15:15-16:15] → No solapan
```

### Caso 4: Excepciones Parciales
```
DATOS:
  - Fecha: 2024-01-20 (sábado)
  - Horario base: 09:00 - 14:00 (sábado es jornada reducida)
  - Excepción: "Cierre por capacitación" 12:00-14:00

HORARIO AJUSTADO:
  - Original: 09:00 - 14:00
  - Después de excepción: 09:00 - 12:00

SLOTS VÁLIDOS: Solo hasta 12:00
  - [09:00-09:30] ✓
  - [09:15-09:45] ✓
  - ...
  - [11:30-12:00] ✓
  - [11:45-12:15] ✗ (termina después de 12:00)
```

---

## Consideraciones de Implementación en Laravel

### Scope Global para Multi-Tenant
```
// En modelo Appointment
protected static function booted()
{
    static::addGlobalScope('business', function (Builder $query) {
        if (Auth::guard('business')->check()) {
            $query->where('business_id', Auth::guard('business')->id());
        }
    });
}
```

### Transacción con Lock Pessimista
```
// En servicio de disponibilidad
DB::transaction(function () {
    $citasExistentes = Appointment::where('employee_id', $employeeId)
        ->lockForUpdate()  // SELECT FOR UPDATE
        ->get();
    
    // Validar solapamiento
    // ...
    
    // Crear cita
    Appointment::create($datos);
});
```

### Query para Filtrar Slots
```
$citasConflicto = Appointment::where([
    ['employee_id', $employeeId],
    ['location_id', $locationId],
    ['estado', '!=', 'cancelled']
])
->whereBetween('fecha_hora_inicio', [$busquedaInicio, $busquedaFin])
->get();
```

---

## Edge Cases y Problemas Comunes

| Caso | Problema | Solución |
|------|----------|----------|
| **Citas canceladas** | ¿Contar para disponibilidad? | NO - filtrar por estado != 'cancelled' |
| **Timezone** | Usuario y sucursal en diferentes zonas | Convertir todas las horas a UTC, luego a zona de sucursal |
| **Citas pasadas** | ¿Contar para disponibilidad futura? | NO - solo 'confirmed' y 'completed' recientes |
| **Cambios concurrentes** | Dos usuarios reservan simultáneamente | Lock pessimista (SELECT FOR UPDATE) |
| **Buffers infinitos** | Buffer > duración del servicio | Validar en creación de servicio |
| **Recurso sin capacidad** | capacidad = 0 | Validar en creación de recurso, mínimo 1 |
| **Empleado sin servicios** | empleado_id inválido para service_id | Validar FK en employee_services |
| **Hora de cierre en medianoche** | 24:00 vs 00:00 | Usar formato 24h, validar tipo TIME en BD |

---

## Performance y Optimizaciones

### Índices Críticos
```sql
CREATE INDEX idx_appointments_employee_date 
    ON appointments(employee_id, fecha_hora_inicio, estado);

CREATE INDEX idx_appointments_resource_date 
    ON appointments(resource_id, fecha_hora_inicio, estado);

CREATE INDEX idx_schedule_exceptions_location_date 
    ON schedule_exceptions(location_id, fecha);
```

### Caching de Slots
```
// Cache por 5 minutos
$cacheKey = "slots:{$businessId}:{$locationId}:{$serviceId}:{$fecha}";
$slots = Cache::remember($cacheKey, 300, function () {
    return generarSlotsDisponibles(...);
});

// Invalidar cache cuando hay cambios
Cache::forget($cacheKey);
```

---

## Conclusión

El motor de disponibilidad es:
1. **Determinístico**: Misma entrada siempre produce misma salida
2. **Seguro contra concurrencia**: Lock pessimista en citas críticas
3. **Escalable**: Caché de slots, paginación en respuesta
4. **Flexible**: Soporta todos los tipos de reglas y excepciones
