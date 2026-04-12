---
name: booking-atomic
description: Patrones de reserva sin doble booking: locks optimistas, atomic transactions, conflict resolution, y UI de disponibilidad confiable para AgendaYa.
---

## Qué hago

Aseguro que las reservas de citas nunca generen doble booking, implementando patrones de concurrencia, validación atómica y resolución de conflictos tanto en backend (Laravel) como en frontend (Flutter).

## Cuándo usarme

Usa este skill cuando:
- Se implemente creación de citas/reservas
- Se consuma el endpoint de disponibilidad `/api/v1/availability/slots`
- Se necesite prevenir doble booking
- Se maneje concurrencia en horarios compartidos
- Se diseñe UI de selección de horario con feedback de disponibilidad

## El problema del doble booking

El doble booking ocurre cuando dos usuarios reservan el mismo slot simultáneamente. Para prevenirlo se necesita protección en múltiples capas:

```
Usuario A lee slots → Slot 10:00 disponible
Usuario B lee slots → Slot 10:00 disponible
Usuario A reserva 10:00 → Éxito
Usuario B reserva 10:00 → DOBLE BOOKING (debe fallar)
```

## Capas de protección

### Capa 1: Database-level (última línea de defensa)

```sql
-- unique constraint compuesto en migración
ALTER TABLE appointments
ADD CONSTRAINT unique_slot_per_employee
UNIQUE (employee_id, date, start_time)
WHERE deleted_at IS NULL;

-- O con partial index en PostgreSQL
CREATE UNIQUE INDEX idx_unique_active_appointment
ON appointments (employee_id, branch_id, date, start_time)
WHERE status NOT IN ('cancelled', 'no_show');
```

```php
// En el modelo Appointment de Laravel
protected static function booted(): void
{
    static::creating(function (Appointment $appointment) {
        $exists = static::query()
            ->where('employee_id', $appointment->employee_id)
            ->where('branch_id', $appointment->branch_id)
            ->where('date', $appointment->date)
            ->where('start_time', $appointment->start_time)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->exists();

        if ($exists) {
            throw new DoubleBookingException(
                'Este horario ya fue reservado. Por favor selecciona otro.'
            );
        }
    });
}
```

### Capa 2: Service-level (con database transaction + lock)

```php
class BookingService
{
    public function createAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $slot = AvailableSlot::query()
                ->where('employee_id', $data['employee_id'])
                ->where('branch_id', $data['branch_id'])
                ->where('date', $data['date'])
                ->where('start_time', $data['start_time'])
                ->lockForUpdate()
                ->first();

            if (!$slot || !$slot->is_available) {
                throw new SlotUnavailableException(
                    'Horario no disponible. Intenta con otro horario.'
                );
            }

            $appointment = Appointment::create([
                'user_id' => auth()->id(),
                'employee_id' => $data['employee_id'],
                'branch_id' => $data['branch_id'],
                'business_id' => $data['business_id'],
                'service_id' => $data['service_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['start_time']->copy()->addMinutes($slot->duration),
                'status' => 'confirmed',
            ]);

            $slot->update(['is_available' => false]);

            return $appointment;
        });
    }
}
```

### Capa 3: API-level (optimistic concurrency)

```php
// Controller endpoint con version/etag para concurrencia optimista
public function store(StoreAppointmentRequest $request): JsonResponse
{
    try {
        $appointment = $this->bookingService->createAppointment(
            $request->validated()
        );

        return response()->json([
            'data' => new AppointmentResource($appointment),
            'message' => 'Cita reservada exitosamente',
        ], 201);

    } catch (DoubleBookingException $e) {
        return response()->json([
            'error' => 'double_booking',
            'message' => $e->getMessage(),
            'suggestion' => 'Refresca los horarios disponibles e intenta de nuevo.',
        ], 409);

    } catch (SlotUnavailableException $e) {
        return response()->json([
            'error' => 'slot_unavailable',
            'message' => $e->getMessage(),
        ], 410);
    }
}
```

### Capa 4: Frontend-level (UX defensiva)

```dart
class BookingController extends ChangeNotifier {
  final ApiClient _api;
  DateTime? _selectedSlot;
  bool _isBooking = false;
  String? _error;

  Future<void> bookSlot({
    required String employeeId,
    required String branchId,
    required String businessId,
    required String serviceId,
    required DateTime date,
    required TimeOfDay startTime,
  }) async {
    if (_isBooking) return;

    setState(() {
      _isBooking = true;
      _error = null;
    });

    try {
      final response = await _api.post('/api/v1/appointments', body: {
        'employee_id': employeeId,
        'branch_id': branchId,
        'business_id': businessId,
        'service_id': serviceId,
        'date': date.toIso8601String().split('T').first,
        'start_time': '${startTime.hour.toString().padLeft(2, '0')}:${startTime.minute.toString().padLeft(2, '0')}',
      });

      setState(() {
        _selectedSlot = null;
        _isBooking = false;
      });

      notifier.showSuccess('¡Cita reservada exitosamente!');

    } on DoubleBookingException {
      setState(() {
        _error = 'Este horario ya fue reservado. Selecciona otro.';
        _isBooking = false;
      });

      await refreshSlots();

    } on SlotUnavailableException {
      setState(() {
        _error = 'Horario no disponible.';
        _isBooking = false;
      });

      await refreshSlots();

    } catch (e) {
      setState(() {
        _error = 'Error de conexión. Intenta de nuevo.';
        _isBooking = false;
      });
    }
  }

  void setState(VoidCallback callback) {
    callback();
    notifyListeners();
  }
}
```

## UI de selección de horario

### Patrones de visualización de slots

```dart
class SlotSelector extends StatefulWidget {
  final List<AvailableSlot> slots;
  final ValueChanged<AvailableSlot> onSelected;
  final bool isLoading;

  const SlotSelector({
    super.key,
    required this.slots,
    required this.onSelected,
    this.isLoading = false,
  });

  @override
  State<SlotSelector> createState() => _SlotSelectorState();
}

class _SlotSelectorState extends State<SlotSelector> {
  AvailableSlot? _selected;

  @override
  Widget build(BuildContext context) {
    if (widget.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (widget.slots.isEmpty) {
      return const EmptyState(
        icon: Icons.event_busy,
        title: 'Sin horarios disponibles',
        subtitle: 'Intenta con otra fecha o empleado',
      );
    }

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: widget.slots.map((slot) {
        final isSelected = _selected == slot;
        return ChoiceChip(
          label: Text(slot.formattedTime),
          selected: isSelected,
          onSelected: (_) {
            setState(() => _selected = slot);
            widget.onSelected(slot);
          },
        );
      }).toList(),
    );
  }
}
```

### Auto-refresh de slots antes de reservar

```dart
mixin SlotRefreshMixin on ChangeNotifier {
  final AvailabilityRepository _availabilityRepo = Get.find();
  DateTime? _lastRefresh;
  List<AvailableSlot> _cachedSlots = [];

  static const _refreshThreshold = Duration(minutes: 2);

  Future<List<AvailableSlot>> getSlots({
    required String employeeId,
    required String branchId,
    required DateTime date,
    bool forceRefresh = false,
  }) async {
    final shouldRefresh = forceRefresh ||
        _lastRefresh == null ||
        DateTime.now().difference(_lastRefresh!) > _refreshThreshold;

    if (!shouldRefresh && _cachedSlots.isNotEmpty) {
      return _cachedSlots;
    }

    _cachedSlots = await _availabilityRepo.getSlots(
      employeeId: employeeId,
      branchId: branchId,
      date: date,
    );
    _lastRefresh = DateTime.now();
    notifyListeners();

    return _cachedSlots;
  }

  Future<void> invalidateCache() async {
    _cachedSlots = [];
    _lastRefresh = null;
  }
}
```

### Booking confirmation flow

```dart
class BookingFlow {
  static Future<void> confirmAndBook(BuildContext context, {
    required AvailableSlot slot,
    required BookingController controller,
  }) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => BookingConfirmDialog(slot: slot),
    );

    if (confirmed != true) return;

    controller.bookSlot(
      employeeId: slot.employeeId,
      branchId: slot.branchId,
      businessId: slot.businessId,
      serviceId: slot.serviceId,
      date: slot.date,
      startTime: slot.startTime,
    );
  }
}
```

## Timezone handling

```php
// Backend: siempre guardar en UTC, mostrar en timezone de sucursal
public function getSlots(Request $request): JsonResponse
{
    $branch = Branch::findOrFail($request->branch_id);
    $timezone = $branch->timezone ?? 'America/Mexico_City';

    $date = Carbon::parse($request->date, $timezone)->toUtc();

    $slots = $this->availabilityService->getSlots(
        employeeId: $request->employee_id,
        branch: $branch,
        date: $date,
    );

    return response()->json([
        'data' => SlotResource::collection($slots),
        'meta' => [
            'timezone' => $timezone,
            'date' => $request->date,
        ],
    ]);
}
```

```dart
// Frontend: convertir UTC a timezone local para mostrar
class SlotTimeFormatter {
  static String format(TimeOfDay time, {String? timezone}) {
    final now = DateTime.now();
    final dt = DateTime(
      now.year, now.month, now.day,
      time.hour, time.minute,
    );
    return DateFormat.Hm().format(dt);
  }

  static String formatWithTimezone(DateTime utcTime, String targetTimezone) {
    final local = utcTime.toLocal();
    return DateFormat.Hm().format(local);
  }
}
```

## Buffer rules (tiempo entre citas)

```php
class BufferRuleService
{
    public function calculateEndTime(
        Appointment $appointment,
        Service $service,
    ): Carbon {
        $bufferAfter = $service->buffer_after ?? 0;
        $bufferBefore = $service->buffer_before ?? 0;

        return $appointment->start_time
            ->copy()
            ->addMinutes($service->duration + $bufferAfter);
    }

    public function isSlotAvailableWithBuffers(
        Carbon $requestedStart,
        int $duration,
        int $bufferBefore,
        int $bufferAfter,
        Collection $existingAppointments,
    ): bool {
        $requestedEnd = $requestedStart->copy()->addMinutes($duration);

        foreach ($existingAppointments as $existing) {
            $existingStart = Carbon::parse($existing->start_time)
                ->subMinutes($bufferBefore);
            $existingEnd = Carbon::parse($existing->end_time)
                ->addMinutes($bufferAfter);

            if ($requestedStart < $existingEnd && $requestedEnd > $existingStart) {
                return false;
            }
        }

        return true;
    }
}
```

## Reglas obligatorias

1. NUNCA permitir doble booking — siempre validar con `lockForUpdate` en la transacción.
2. SIEMPRE usar DB transaction para crear cita + actualizar slot.
3. SIEMPRE refrescar slots antes de mostrar confirmación de reserva.
4. SIEMPRE manejar error 409 (double_booking) en el frontend con refresh automático.
5. SIEMPRE respetar el timezone de la sucursal, nunca asumir `America/Mexico_City`.
6. SIEMPRE aplicar buffers (before/after) al calcular disponibilidad.
7. NUNCA confiar solo en el frontend para validación de disponibilidad.
8. SIEMPRE mostrar feedback claro al usuario cuando un slot ya no está disponible.

## Códigos de error API

| Código HTTP | Error | Acción frontend |
|------------|-------|----------------|
| 409 | `double_booking` | Refrescar slots y mostrar mensaje |
| 410 | `slot_unavailable` | Refrescar slots y mostrar alternativas |
| 422 | `validation_error` | Corregir campos inválidos |
| 429 | `rate_limit` | Backoff y reintentar |
| 500 | `server_error` | Mostrar error genérico, reintentar después |