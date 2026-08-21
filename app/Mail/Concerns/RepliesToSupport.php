<?php

namespace App\Mail\Concerns;

use Illuminate\Mail\Mailables\Address;

trait RepliesToSupport
{
    /**
     * Sender for lifecycle mail. These invite a reply, so they come from a
     * monitored alias rather than the no-reply sender. Null keeps the default.
     */
    protected function lifecycleFrom(): ?Address
    {
        $address = config('mail.lifecycle_from.address');

        return $address ? new Address($address, config('mail.lifecycle_from.name')) : null;
    }

    /**
     * @return array<int, Address>
     */
    protected function supportReplyTo(): array
    {
        $support = config('services.support.email');

        return $support ? [new Address($support, config('app.name'))] : [];
    }
}
