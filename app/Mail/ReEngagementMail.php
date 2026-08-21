<?php

namespace App\Mail;

use App\Mail\Concerns\RepliesToSupport;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ReEngagementMail extends Mailable
{
    use Queueable, RepliesToSupport, SerializesModels;

    /** @param Collection<int, Bid> $missedBids */
    public int $total;

    /** @param Collection<int, Bid> $missedBids */
    public function __construct(
        public User $user,
        public int $daysAway,
        public Collection $missedBids,
        ?int $total = null,
    ) {
        // The list is capped for display; the total is the real match count.
        $this->total = $total ?? $missedBids->count();
    }

    public function envelope(): Envelope
    {
        $count = $this->total;
        $first = Str::of((string) $this->user->name)->before(' ')->trim();

        $subject = $count === 1
            ? 'encontramos 1 licitación para ti'
            : "encontramos {$count} licitaciones para ti";
        $subject = $first->isNotEmpty() ? "{$first}, {$subject}" : ucfirst($subject);

        // Real urgency only — taken from the soonest actual closing date.
        if ($days = $this->daysToSoonestDeadline()) {
            $subject .= $days <= 1 ? ' (una cierra mañana)' : " (una cierra en {$days} días)";
        }

        return new Envelope(
            subject: $subject,
            from: $this->lifecycleFrom(),
            replyTo: $this->supportReplyTo(),
        );
    }

    private function daysToSoonestDeadline(): ?int
    {
        $soonest = $this->missedBids
            ->filter(fn ($b) => $b->tender_deadline && $b->tender_deadline->isFuture())
            ->sortBy('tender_deadline')
            ->first();

        if (! $soonest) {
            return null;
        }

        $days = (int) ceil(now()->floatDiffInDays($soonest->tender_deadline));

        return $days <= 7 ? max(1, $days) : null;
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.re-engagement',
            with: [
                'name' => $this->user->name,
                'total' => $this->total,
                'highlights' => $this->missedBids->take(5),
                'soonestDays' => $this->daysToSoonestDeadline(),
                'unsubscribeUrl' => URL::signedRoute('lifecycle.unsubscribe', ['user' => $this->user->getKey()]),
                'url' => route('convocatorias.index'),
            ],
        );
    }
}
