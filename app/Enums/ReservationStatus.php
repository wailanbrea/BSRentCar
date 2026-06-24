<?php

namespace App\Enums;

/**
 * Estados de la reserva y transiciones. Ver docs/10_RESERVATIONS_FLOW.md.
 */
enum ReservationStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Confirmed = 'confirmed';
    case InPreparation = 'in_preparation';
    case ContractPending = 'contract_pending';
    case ContractSigned = 'contract_signed';
    case DeliveryAssigned = 'delivery_assigned';
    case Delivered = 'delivered';
    case Active = 'active';
    case ReturnPending = 'return_pending';
    case Returned = 'returned';
    case InspectionPending = 'inspection_pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case NoShow = 'no_show';
    case Expired = 'expired';

    /**
     * Estados que BLOQUEAN disponibilidad del vehículo (BR-R08).
     *
     * @return list<self>
     */
    public static function blocking(): array
    {
        return [
            self::Paid,
            self::Confirmed,
            self::InPreparation,
            self::ContractSigned,
            self::DeliveryAssigned,
            self::Delivered,
            self::Active,
            self::ReturnPending,
        ];
    }

    /**
     * @return list<string>
     */
    public static function blockingValues(): array
    {
        return array_map(fn (self $s) => $s->value, self::blocking());
    }
}
