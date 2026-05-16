<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
        'current_company_id',
        'last_sign_in_at',
        'newsletter_subscribed',
        'newsletter_consented_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_sign_in_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'newsletter_subscribed' => 'boolean',
            'newsletter_consented_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withPivot('joined_at')->withTimestamps();
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function currentCompanyRelation(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function googleCalendarTokens(): HasMany
    {
        return $this->hasMany(GoogleCalendarToken::class);
    }

    // ── Auth helpers ─────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function isSubscriptionOwner(): bool
    {
        return $this->subscription !== null;
    }

    public function currentCompany(): ?Company
    {
        if ($this->current_company_id) {
            return $this->currentCompanyRelation;
        }

        return $this->companies->first();
    }

    public function belongsToCompany(int $companyId): bool
    {
        return $this->companies()->where('companies.id', $companyId)->exists();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
