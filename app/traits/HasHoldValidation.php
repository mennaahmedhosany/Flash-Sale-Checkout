<?php


namespace App\Traits;

use Illuminate\Validation\ValidationException;

trait HasHoldValidation
{
    public function validateUsable(): void
    {
        if ($this->is_redeemed) {
            throw ValidationException::withMessages([
                'hold_id' => 'Hold has already been redeemed by an order.'
            ]);
        }

        if ($this->released_at) {
            throw ValidationException::withMessages([
                'hold_id' => 'Hold has expired and stock was released.'
            ]);
        }

        if ($this->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'hold_id' => 'Hold is expired.'
            ]);
        }
    }
}
