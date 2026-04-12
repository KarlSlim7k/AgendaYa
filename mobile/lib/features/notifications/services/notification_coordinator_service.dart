import 'package:agenda_ya/core/constants/api_constants.dart';
import 'package:agenda_ya/core/services/local_notification_service.dart';
import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/data/providers/api_client.dart';
import 'package:agenda_ya/features/notifications/models/notification_delivery_log.dart';
import 'package:agenda_ya/features/notifications/services/appointment_reminder_service.dart';
import 'package:agenda_ya/features/notifications/services/notification_delivery_log_service.dart';
import 'package:agenda_ya/features/notifications/services/notification_preferences_service.dart';

class NotificationCoordinatorService {
  final NotificationPreferencesService _preferencesService =
      NotificationPreferencesService();
  final NotificationDeliveryLogService _logService =
      NotificationDeliveryLogService();
  final AppointmentReminderService _reminderService = AppointmentReminderService();
  final LocalNotificationService _localNotificationService =
      LocalNotificationService.instance;
  final ApiClient _apiClient = ApiClient();

  Future<void> onAppointmentConfirmed(Appointment appointment) async {
    final localSent = await _localNotificationService
        .showAppointmentConfirmationNotification(
      appointmentId: appointment.id,
      serviceName: appointment.serviceName ?? 'Tu cita',
      startAt: appointment.fechaHoraInicio,
    );

    await _logService.addLog(
      NotificationDeliveryLog(
        id: 'log_${DateTime.now().microsecondsSinceEpoch}_push_confirm',
        appointmentId: appointment.id,
        channel: 'push_local',
        event: 'confirmacion',
        status: localSent ? 'enviado' : 'fallido',
        message: localSent
            ? 'Confirmación local enviada al dispositivo.'
            : 'No fue posible mostrar la notificación local de confirmación.',
        createdAt: DateTime.now(),
      ),
    );

    await _logService.addLog(
      NotificationDeliveryLog(
        id: 'log_${DateTime.now().microsecondsSinceEpoch}_email_confirm',
        appointmentId: appointment.id,
        channel: 'email',
        event: 'confirmacion',
        status: 'enviado',
        message: 'Confirmación por email gestionada en backend.',
        createdAt: DateTime.now(),
      ),
    );

    await _reminderService.upsertFromAppointment(appointment);
    await _reminderService.processDueReminders();

    final whatsappEnabled =
        await _preferencesService.getWhatsAppRemindersEnabled();

    if (!whatsappEnabled) {
      return;
    }

    await _dispatchWhatsAppReminder(
      appointment: appointment,
      hoursBefore: 24,
      event: 'recordatorio_24h',
    );

    await _dispatchWhatsAppReminder(
      appointment: appointment,
      hoursBefore: 1,
      event: 'recordatorio_1h',
    );
  }

  Future<void> onAppointmentsSynced(List<Appointment> appointments) async {
    await _reminderService.syncFromAppointments(appointments);
    await _reminderService.processDueReminders();
  }

  Future<void> onAppointmentCancelled(Appointment appointment) async {
    await _localNotificationService.cancelAppointmentReminderNotifications(
      appointment.id,
    );
    await _reminderService.removeByAppointmentId(appointment.id);

    await _logService.addLog(
      NotificationDeliveryLog(
        id: 'log_${DateTime.now().microsecondsSinceEpoch}_cancel_reminders',
        appointmentId: appointment.id,
        channel: 'push_local',
        event: 'cancelacion',
        status: 'enviado',
        message: 'Recordatorios locales cancelados por cancelación de cita.',
        createdAt: DateTime.now(),
      ),
    );
  }

  Future<void> processPendingLocalReminders() async {
    await _reminderService.processDueReminders();
  }

  Future<void> _dispatchWhatsAppReminder({
    required Appointment appointment,
    required int hoursBefore,
    required String event,
  }) async {
    try {
      final response = await _apiClient.post(
        ApiConstants.appointmentNotificationDispatch(appointment.id),
        body: {
          'channel': 'whatsapp',
          'hours_before': hoursBefore,
        },
      );
      _apiClient.handleResponse(response);

      await _logService.addLog(
        NotificationDeliveryLog(
          id: 'log_${DateTime.now().microsecondsSinceEpoch}_wa_$hoursBefore',
          appointmentId: appointment.id,
          channel: 'whatsapp',
          event: event,
          status: 'enviado',
          message: 'Recordatorio de WhatsApp enviado.',
          createdAt: DateTime.now(),
        ),
      );
    } catch (error) {
      await _logService.addLog(
        NotificationDeliveryLog(
          id: 'log_${DateTime.now().microsecondsSinceEpoch}_wa_fail_$hoursBefore',
          appointmentId: appointment.id,
          channel: 'whatsapp',
          event: event,
          status: 'fallido',
          message: 'WhatsApp no disponible. Se activa fallback por email.',
          createdAt: DateTime.now(),
          metadata: {
            'error': error.toString(),
          },
        ),
      );

      await _logService.addLog(
        NotificationDeliveryLog(
          id: 'log_${DateTime.now().microsecondsSinceEpoch}_fallback_$hoursBefore',
          appointmentId: appointment.id,
          channel: 'email',
          event: 'fallback_email',
          status: 'enviado',
          message: 'Fallback a email aplicado por falla de WhatsApp.',
          createdAt: DateTime.now(),
          metadata: {
            'source_event': event,
            'hours_before': hoursBefore,
          },
        ),
      );
    }
  }
}
