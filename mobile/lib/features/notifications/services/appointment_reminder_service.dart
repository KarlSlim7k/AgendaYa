import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import 'package:agenda_ya/core/constants/app_constants.dart';
import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/core/services/local_notification_service.dart';
import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/features/notifications/models/appointment_reminder_plan.dart';
import 'package:agenda_ya/features/notifications/models/notification_delivery_log.dart';
import 'package:agenda_ya/features/notifications/services/notification_delivery_log_service.dart';

class AppointmentReminderService {
  final NotificationDeliveryLogService _logService =
      NotificationDeliveryLogService();

  Future<void> upsertFromAppointment(
    Appointment appointment, {
    bool logProgrammedReminders = true,
  }) async {
    if (!(appointment.isPending || appointment.isConfirmed)) {
      return;
    }

    final now = DateTime.now();
    if (appointment.fechaHoraInicio.toLocal().isBefore(now.subtract(const Duration(hours: 2)))) {
      return;
    }

    final plans = await _readPlans();
    final index = plans.indexWhere(
      (plan) => plan.appointmentId == appointment.id,
    );

    final detailRoute = AppRoutes.appointmentDetailDeepLink(appointment.id);
    final serviceName = appointment.serviceName ?? 'Tu cita';

    final existing = index >= 0 ? plans[index] : null;
    final updated = AppointmentReminderPlan(
      appointmentId: appointment.id,
      serviceName: serviceName,
      startAt: appointment.fechaHoraInicio.toLocal(),
      detailRoute: detailRoute,
      sent24h: existing?.sent24h ?? false,
      sent1h: existing?.sent1h ?? false,
    );

    if (index >= 0) {
      plans[index] = updated;
    } else {
      plans.add(updated);
    }

    await _writePlans(plans);

    if (logProgrammedReminders && index < 0) {
      await _logService.addLog(
        NotificationDeliveryLog(
          id: 'log_${DateTime.now().microsecondsSinceEpoch}_r24',
          appointmentId: appointment.id,
          channel: 'push_local',
          event: 'recordatorio_24h',
          status: 'programado',
          message: 'Recordatorio local preparado para 24h antes.',
          createdAt: DateTime.now(),
        ),
      );

      await _logService.addLog(
        NotificationDeliveryLog(
          id: 'log_${DateTime.now().microsecondsSinceEpoch}_r1',
          appointmentId: appointment.id,
          channel: 'push_local',
          event: 'recordatorio_1h',
          status: 'programado',
          message: 'Recordatorio local preparado para 1h antes.',
          createdAt: DateTime.now(),
        ),
      );
    }
  }

  Future<void> syncFromAppointments(List<Appointment> appointments) async {
    final now = DateTime.now();
    final plans = await _readPlans();
    final planByAppointmentId = {
      for (final plan in plans) plan.appointmentId: plan,
    };

    final activeAppointments = appointments
        .where((appointment) => appointment.isPending || appointment.isConfirmed)
        .where(
          (appointment) =>
              appointment.fechaHoraInicio.toLocal().isAfter(now.subtract(const Duration(hours: 2))),
        )
        .toList();

    final syncedPlans = <AppointmentReminderPlan>[];
    for (final appointment in activeAppointments) {
      final existing = planByAppointmentId[appointment.id];
      syncedPlans.add(
        AppointmentReminderPlan(
          appointmentId: appointment.id,
          serviceName: appointment.serviceName ?? 'Tu cita',
          startAt: appointment.fechaHoraInicio.toLocal(),
          detailRoute: AppRoutes.appointmentDetailDeepLink(appointment.id),
          sent24h: existing?.sent24h ?? false,
          sent1h: existing?.sent1h ?? false,
        ),
      );
    }

    await _writePlans(syncedPlans);
  }

  Future<void> removeByAppointmentId(int appointmentId) async {
    final plans = await _readPlans();
    plans.removeWhere((plan) => plan.appointmentId == appointmentId);
    await _writePlans(plans);
  }

  Future<void> processDueReminders() async {
    final plans = await _readPlans();
    if (plans.isEmpty) {
      return;
    }

    final now = DateTime.now();
    final updatedPlans = <AppointmentReminderPlan>[];
    var hasChanges = false;

    for (final originalPlan in plans) {
      var plan = originalPlan;
      final startAt = plan.startAt.toLocal();

      if (startAt.isBefore(now.subtract(const Duration(hours: 3)))) {
        hasChanges = true;
        continue;
      }

      final reminder24At = startAt.subtract(const Duration(hours: 24));
      if (!plan.sent24h && now.isAfter(reminder24At) && now.isBefore(startAt)) {
        final sent = await LocalNotificationService.instance
            .showAppointmentReminderNotification(
          appointmentId: plan.appointmentId,
          serviceName: plan.serviceName,
          startAt: startAt,
          hoursBefore: 24,
        );

        await _logService.addLog(
          NotificationDeliveryLog(
            id: 'log_${DateTime.now().microsecondsSinceEpoch}_due24',
            appointmentId: plan.appointmentId,
            channel: 'push_local',
            event: 'recordatorio_24h',
            status: sent ? 'enviado' : 'fallido',
            message: sent
                ? 'Recordatorio local de 24h enviado.'
                : 'No fue posible enviar el recordatorio local de 24h.',
            createdAt: DateTime.now(),
          ),
        );

        plan = plan.copyWith(sent24h: true);
        hasChanges = true;
      }

      final reminder1At = startAt.subtract(const Duration(hours: 1));
      if (!plan.sent1h && now.isAfter(reminder1At) && now.isBefore(startAt)) {
        final sent = await LocalNotificationService.instance
            .showAppointmentReminderNotification(
          appointmentId: plan.appointmentId,
          serviceName: plan.serviceName,
          startAt: startAt,
          hoursBefore: 1,
        );

        await _logService.addLog(
          NotificationDeliveryLog(
            id: 'log_${DateTime.now().microsecondsSinceEpoch}_due1',
            appointmentId: plan.appointmentId,
            channel: 'push_local',
            event: 'recordatorio_1h',
            status: sent ? 'enviado' : 'fallido',
            message: sent
                ? 'Recordatorio local de 1h enviado.'
                : 'No fue posible enviar el recordatorio local de 1h.',
            createdAt: DateTime.now(),
          ),
        );

        plan = plan.copyWith(sent1h: true);
        hasChanges = true;
      }

      if (now.isAfter(startAt)) {
        if (!plan.sent24h || !plan.sent1h) {
          plan = plan.copyWith(sent24h: true, sent1h: true);
          hasChanges = true;
        }
      }

      updatedPlans.add(plan);
    }

    if (hasChanges) {
      await _writePlans(updatedPlans);
    }
  }

  Future<List<AppointmentReminderPlan>> _readPlans() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(AppConstants.reminderPlansKey);

    if (raw == null || raw.isEmpty) {
      return <AppointmentReminderPlan>[];
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List) {
        return <AppointmentReminderPlan>[];
      }

      return decoded
          .whereType<Map>()
          .map(
            (item) => AppointmentReminderPlan.fromJson(
              Map<String, dynamic>.from(item),
            ),
          )
          .toList();
    } catch (_) {
      return <AppointmentReminderPlan>[];
    }
  }

  Future<void> _writePlans(List<AppointmentReminderPlan> plans) async {
    final prefs = await SharedPreferences.getInstance();
    final encoded = jsonEncode(plans.map((plan) => plan.toJson()).toList());
    await prefs.setString(AppConstants.reminderPlansKey, encoded);
  }
}
