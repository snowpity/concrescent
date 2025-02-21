<?php

namespace App\Database\Model;

class ShortAttendee {
    private(set) string $type = 'attendee';

    public string $idString {
        get => 'A'.$this->id;
    }

    public string $badgeTypeIdString {
        get => 'AB'.$this->badgeTypeId;
    }

    public function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $dateCreated,
        public readonly string $dateModified,
        public readonly ?string $printCount,
        public readonly ?string $checkinCount,
        public readonly string $badgeTypeId,
        public readonly string $badgeTypeName,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $fandomName,
        public readonly bool $subscribed,
        public readonly string $emailAdress,
        public readonly PaymentStatus $paymentStatus,
        public readonly ?string $paymentPromoCode,
    ) {}
}
