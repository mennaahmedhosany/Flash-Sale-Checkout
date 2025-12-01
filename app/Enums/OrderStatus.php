<?php

namespace App\Enums;

enum OrderStatus: string
{
    case pending_payment = 'pending_payment';
    case paid = 'paid';
    case cancelled = 'cancelled';
    case failed = 'failed';
}
