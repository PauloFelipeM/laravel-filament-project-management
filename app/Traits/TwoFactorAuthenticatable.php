<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Models\BreezySession;

trait TwoFactorAuthenticatable
{
    public static function bootTwoFactorAuthenticatable()
    {
        static::deleting(function ($model) {
            $model->breezySessions()->get()->each->delete();
        });
    }

    public function initializeTwoFactorAuthenticatable()
    {
        $this->with[] = 'breezySessions';
    }

    public function breezySessions()
    {
        return $this->morphMany(BreezySession::class, 'authenticatable');
    }

    public function breezySession(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->breezySessions->first()
        );
    }

    public function hasEnabledTwoFactor(): bool
    {
        return $this->breezySession?->is_enabled ?? false;
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->breezySession?->is_confirmed ?? false;
    }

    public function twoFactorRecoveryCodes(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->breezySession ? json_decode(decrypt(
                $this->breezySession->two_factor_recovery_codes), true) : null
        );
    }

    public function twoFactorSecret(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->breezySession?->two_factor_secret
        );
    }

    public function enableTwoFactorAuthentication()
    {
        $twoFactorData = [
            'two_factor_secret' => encrypt(filament('filament-breezy')->getEngine()->generateSecretKey()),
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
        ];
        if ($this->breezy_session) {
            $this->disableTwoFactorAuthentication(); // Delete the session if it exists.
        }
        $this->breezySession = $this->breezySessions()->create($twoFactorData);
        $this->load('breezySessions');
    }

    public function disableTwoFactorAuthentication()
    {
        $this->breezySession?->delete();
    }

    public function confirmTwoFactorAuthentication()
    {
        $this->breezySession?->confirm();
        $this->setTwoFactorSession();
    }

    public function setTwoFactorSession(?int $lifetime = null)
    {
        $this->breezySession->setSession($lifetime);
    }

    public function hasValidTwoFactorSession(): bool
    {
        return $this->breezySession?->is_valid ?? false;
    }

    public function generateRecoveryCodes()
    {
        return encrypt(json_encode(Collection::times(8, function () {
            return Str::random(10).'-'.Str::random(10);
        })->all()));
    }

    public function destroyRecoveryCode(string $recoveryCode): void
    {
        $unusedCodes = array_filter($this->two_factor_recovery_codes ?? [], fn ($code) => $code !== $recoveryCode);

        $this->breezy_session->forceFill([
            'two_factor_recovery_codes' => $unusedCodes ? encrypt(json_encode($unusedCodes)) : null,
        ])->save();
    }

    public function getTwoFactorQrCodeUrl()
    {
        return filament('filament-breezy')->getQrCodeUrl(
            config('app.name'),
            $this->email,
            decrypt($this->breezySession->two_factor_secret)
        );
    }

    public function reGenerateRecoveryCodes()
    {
        $this->breezy_session->forceFill([
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
        ])->save();
    }
}
