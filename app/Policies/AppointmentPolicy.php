<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier cita
     * - Usuario final: Solo sus propias citas
     * - Staff/Manager/Admin: Citas de su negocio
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtrado en query del controller
    }

    /**
     * Determinar si el usuario puede ver una cita específica
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // El usuario es dueño de la cita
        if ($appointment->user_id === $user->id) {
            return true;
        }

        // El usuario tiene permiso de lectura en el negocio de la cita
        return $user->hasPermissionInBusiness('cita.read', $appointment->business_id);
    }

    /**
     * Determinar si el usuario puede crear citas
     */
    public function create(User $user): bool
    {
        // Cualquier usuario autenticado puede crear citas como cliente
        return true;
    }

    /**
     * Determinar si el usuario puede actualizar una cita
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Verificar pertenencia al tenant
        if ($user->current_business_id && $appointment->business_id !== $user->current_business_id) {
            return false;
        }

        // Usuario final no puede actualizar citas (solo cancelar)
        if ($appointment->user_id === $user->id) {
            return false;
        }

        // Staff/Manager/Admin con permiso en el negocio
        return $user->hasPermissionInBusiness('cita.update', $appointment->business_id);
    }

    /**
     * Determinar si el usuario puede cancelar una cita
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        // El usuario es dueño de la cita
        if ($appointment->user_id === $user->id) {
            return true;
        }

        // Staff/Manager/Admin con permiso en el negocio
        if ($user->current_business_id && $appointment->business_id !== $user->current_business_id) {
            return false;
        }

        return $user->hasPermissionInBusiness('cita.update', $appointment->business_id);
    }

    /**
     * Determinar si el usuario puede eliminar una cita
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        // Solo Admin del negocio puede eliminar (soft delete)
        if ($user->current_business_id && $appointment->business_id !== $user->current_business_id) {
            return false;
        }

        return $user->hasPermissionInBusiness('cita.delete', $appointment->business_id);
    }

    /**
     * Determinar si el usuario puede restaurar una cita eliminada
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        if ($user->current_business_id && $appointment->business_id !== $user->current_business_id) {
            return false;
        }

        return $user->hasPermissionInBusiness('cita.delete', $appointment->business_id);
    }

    /**
     * Determinar si el usuario puede forzar eliminación permanente
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        // Solo PLATAFORMA_ADMIN puede hacer force delete
        return false;
    }

    /**
     * Determinar si el usuario puede ver notas internas
     */
    public function viewInternalNotes(User $user, Appointment $appointment): bool
    {
        if ($user->current_business_id && $appointment->business_id !== $user->current_business_id) {
            return false;
        }

        return $user->hasPermissionInBusiness('cita.read', $appointment->business_id);
    }

    /**
     * Determinar si el usuario puede cambiar el estado de una cita
     */
    public function changeStatus(User $user, Appointment $appointment): bool
    {
        if ($user->current_business_id && $appointment->business_id !== $user->current_business_id) {
            return false;
        }

        return $user->hasPermissionInBusiness('cita.update', $appointment->business_id);
    }
}
