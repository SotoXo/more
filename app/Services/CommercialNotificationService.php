<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use App\Notifications\CommercialNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CommercialNotificationService
{
    public function notifyCustomerReservationCreated(Reserva $reserva): void
    {
        $this->notifyCustomer($reserva->cliente, new CommercialNotification(
            'Reserva enviada a revisión',
            'Tu reserva ' . $reserva->nre_res . ' fue enviada a revisión.',
            'reserva_creada',
            '/cuenta/reservas',
            'calendar',
            'info',
            'reserva',
            (int) $reserva->id_reserva,
        ));
    }

    public function notifyAdminsReservationPending(Reserva $reserva): void
    {
        $this->notifyOperationsTeam(new CommercialNotification(
            'Nueva reserva pendiente de revisión',
            'La reserva ' . $reserva->nre_res . ' tiene un comprobante pendiente de revisión.',
            'pago_pendiente',
            '/superadmin/comercial/reservas/' . $reserva->id_reserva,
            'receipt',
            'warning',
            'reserva',
            (int) $reserva->id_reserva,
        ));
    }

    public function notifyCustomerReservationConfirmed(Reserva $reserva): void
    {
        $message = match ($reserva->est_logistico_res) {
            'lista_recojo' => 'Tu reserva ' . $reserva->nre_res . ' fue confirmada y ya está lista para recojo.',
            'pendiente_traslado' => 'Tu reserva ' . $reserva->nre_res . ' fue confirmada. Estará disponible para recojo desde ' . $this->dateForMessage($reserva->fec_disponible_recojo_res) . '.',
            default => 'Tu reserva ' . $reserva->nre_res . ' fue confirmada.',
        };

        $this->notifyCustomer($reserva->cliente, new CommercialNotification(
            'Reserva confirmada',
            $message,
            $reserva->est_logistico_res === 'lista_recojo' ? 'reserva_lista_recojo' : 'reserva_confirmada',
            '/cuenta/reservas',
            'check-circle',
            'success',
            'reserva',
            (int) $reserva->id_reserva,
        ));
    }

    public function notifyCustomerReservationRejected(Reserva $reserva): void
    {
        $this->notifyCustomer($reserva->cliente, new CommercialNotification(
            'Reserva rechazada',
            'Tu reserva ' . $reserva->nre_res . ' fue rechazada. Revisa la observación o comunícate con la tienda.',
            'reserva_rechazada',
            '/cuenta/reservas',
            'x-circle',
            'error',
            'reserva',
            (int) $reserva->id_reserva,
        ));
    }

    public function notifyCustomerReservationCancelled(Reserva $reserva): void
    {
        $this->notifyCustomer($reserva->cliente, new CommercialNotification(
            'Reserva cancelada',
            'Tu reserva ' . $reserva->nre_res . ' fue cancelada.',
            'reserva_cancelada',
            '/cuenta/reservas',
            'x-circle',
            'warning',
            'reserva',
            (int) $reserva->id_reserva,
        ));
    }

    public function notifyCustomerReservationReadyForPickup(Reserva $reserva): void
    {
        $this->notifyCustomer($reserva->cliente, new CommercialNotification(
            'Reserva lista para recojo',
            'Tu reserva ' . $reserva->nre_res . ' ya está lista para recojo.',
            'reserva_lista_recojo',
            '/cuenta/reservas',
            'package-check',
            'success',
            'reserva',
            (int) $reserva->id_reserva,
        ));
    }

    public function notifyCustomerReservationPickedUp(Reserva $reserva): void
    {
        $this->notifyCustomer($reserva->cliente, new CommercialNotification(
            'Reserva recogida',
            'Tu reserva ' . $reserva->nre_res . ' fue recogida y la venta fue completada.',
            'reserva_recogida',
            '/cuenta/reservas',
            'shopping-bag',
            'success',
            'reserva',
            (int) $reserva->id_reserva,
        ));
    }

    public function notifyCustomerSaleCreated(Venta $venta): void
    {
        $this->notifyCustomer($venta->cliente, new CommercialNotification(
            'Compra enviada a revisión',
            'Tu compra ' . $venta->nve_ven . ' fue enviada a revisión.',
            'venta_creada',
            '/cuenta/pedidos',
            'shopping-bag',
            'info',
            'venta',
            (int) $venta->id_venta,
        ));
    }

    public function notifyAdminsSalePending(Venta $venta): void
    {
        $this->notifyOperationsTeam(new CommercialNotification(
            'Nueva compra online pendiente de revisión',
            'La compra ' . $venta->nve_ven . ' tiene un comprobante pendiente de revisión.',
            'pago_pendiente',
            '/superadmin/comercial/ventas/' . $venta->id_venta,
            'receipt',
            'warning',
            'venta',
            (int) $venta->id_venta,
        ));
    }

    public function notifyCustomerSaleConfirmed(Venta $venta): void
    {
        $message = match ($venta->est_logistico_ven) {
            'lista_recojo' => 'Tu compra ' . $venta->nve_ven . ' fue confirmada y ya está lista para recojo.',
            'pendiente_traslado' => 'Tu compra ' . $venta->nve_ven . ' fue confirmada. Estará disponible para recojo desde ' . $this->dateForMessage($venta->fec_disponible_recojo_ven) . '.',
            default => 'Tu compra ' . $venta->nve_ven . ' fue confirmada.',
        };

        $this->notifyCustomer($venta->cliente, new CommercialNotification(
            'Compra confirmada',
            $message,
            $venta->est_logistico_ven === 'lista_recojo' ? 'venta_lista_recojo' : 'venta_confirmada',
            '/cuenta/pedidos',
            'check-circle',
            'success',
            'venta',
            (int) $venta->id_venta,
        ));
    }

    public function notifyCustomerSaleCancelled(Venta $venta): void
    {
        $this->notifyCustomer($venta->cliente, new CommercialNotification(
            'Compra no confirmada',
            'Tu compra ' . $venta->nve_ven . ' fue cancelada o no pudo ser confirmada.',
            'venta_cancelada',
            '/cuenta/pedidos',
            'x-circle',
            'warning',
            'venta',
            (int) $venta->id_venta,
        ));
    }

    public function notifyCustomerSaleReadyForPickup(Venta $venta): void
    {
        $this->notifyCustomer($venta->cliente, new CommercialNotification(
            'Compra lista para recojo',
            'Tu compra ' . $venta->nve_ven . ' ya está lista para recojo.',
            'venta_lista_recojo',
            '/cuenta/pedidos',
            'package-check',
            'success',
            'venta',
            (int) $venta->id_venta,
        ));
    }

    public function notifyCustomerSaleDelivered(Venta $venta): void
    {
        $this->notifyCustomer($venta->cliente, new CommercialNotification(
            'Compra entregada',
            'Tu compra ' . $venta->nve_ven . ' fue entregada correctamente.',
            'venta_entregada',
            '/cuenta/pedidos',
            'shopping-bag',
            'success',
            'venta',
            (int) $venta->id_venta,
        ));
    }

    private function notifyCustomer(?User $customer, CommercialNotification $notification): void
    {
        if (!$customer || !$customer->isActive()) {
            return;
        }

        $customer->notify($notification);
    }

    private function notifyOperationsTeam(CommercialNotification $notification): void
    {
        $this->operationsTeam()->each(
            fn (User $user) => $user->notify($notification)
        );
    }

    private function operationsTeam(): EloquentCollection
    {
        return User::query()
            ->with('rol')
            ->where('est_usu', true)
            ->whereHas('rol', function ($query) {
                $query
                    ->where('est_rol', true)
                    ->whereRaw('LOWER(nom_rol) IN (?, ?)', ['superadmin', 'vendedor']);
            })
            ->get();
    }

    private function dateForMessage(mixed $date): string
    {
        if (!$date) {
            return 'la fecha indicada';
        }

        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }

        return (string) $date;
    }
}
