<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class VerificationCodeService
{
    protected int $expiryMinutes = 10;
    protected int $dailyLimit = 5;

    // Wrong-code limit: after this many wrong tries, verifying is locked.
    protected int $maxVerifyAttempts = 5;
    protected int $verifyLockSeconds = 600; // 10 minutes

    // Escalating wait time required before the next send is allowed.
    // Index 0 = wait required after the 1st send, before the 2nd.
    // Index 1 = wait required after the 2nd send, before the 3rd.
    // Anything beyond that uses the last value (600s = 10 minutes).
    protected array $cooldownSchedule = [60, 120, 600];

    /**
     * Checks whether a new code is allowed to be sent right now.
     * Returns ['allowed' => bool, 'reason' => string|null, 'wait_seconds' => int|null]
     */
    public function canSend(string $email, string $purpose): array
    {
        $sends = DB::table('tbl_verification_codes')
            ->where('email_address', $email)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('created_at')
            ->get();

        if ($sends->count() >= $this->dailyLimit) {
            return ['allowed' => false, 'reason' => 'daily_limit', 'wait_seconds' => null];
        }

        $latest = $sends->first();

        if (!$latest) {
            return ['allowed' => true, 'reason' => null, 'wait_seconds' => null];
        }

        $index = min($sends->count() - 1, count($this->cooldownSchedule) - 1);
        $requiredWait = $this->cooldownSchedule[$index];
        $elapsed = now()->diffInSeconds($latest->created_at, true);

        if ($elapsed < $requiredWait) {
            return [
                'allowed' => false,
                'reason' => 'cooldown',
                'wait_seconds' => $requiredWait - $elapsed,
            ];
        }

        return ['allowed' => true, 'reason' => null, 'wait_seconds' => null];
    }

    public function generate(string $email, string $purpose): string
    {
        $code = (string) random_int(100000, 999999);

        DB::table('tbl_verification_codes')->insert([
            'email_address' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($this->expiryMinutes),
            'created_at' => now(),
        ]);

        // A fresh code starts with a fresh wrong-attempt counter.
        RateLimiter::clear($this->attemptKey($email, $purpose));

        return $code; // plaintext returned once, only to be emailed — never stored
    }

    /* =========================================================
     * Wrong-code lockout
     * ========================================================= */

    protected function attemptKey(string $email, string $purpose): string
    {
        return 'verify-code|' . $purpose . '|' . sha1(mb_strtolower($email));
    }

    public function isLockedOut(string $email, string $purpose): bool
    {
        return RateLimiter::tooManyAttempts($this->attemptKey($email, $purpose), $this->maxVerifyAttempts);
    }

    public function lockoutSeconds(string $email, string $purpose): int
    {
        return RateLimiter::availableIn($this->attemptKey($email, $purpose));
    }

    public function verify(string $email, string $purpose, string $code): bool
    {
        $key = $this->attemptKey($email, $purpose);

        // Locked out: reject even a correct code until the lock expires.
        if (RateLimiter::tooManyAttempts($key, $this->maxVerifyAttempts)) {
            return false;
        }

        $record = DB::table('tbl_verification_codes')
            ->where('email_address', $email)
            ->where('purpose', $purpose)
            ->latest('created_at')
            ->first();

        if (!$record
            || now()->greaterThan($record->expires_at)
            || !Hash::check($code, $record->code_hash)) {
            RateLimiter::hit($key, $this->verifyLockSeconds);

            return false;
        }

        RateLimiter::clear($key);

        DB::table('tbl_verification_codes')
            ->where('id', $record->id)
            ->update(['verified_at' => now()]);

        return true;
    }

    public function isVerified(string $email, string $purpose): bool
    {
        $record = DB::table('tbl_verification_codes')
            ->where('email_address', $email)
            ->where('purpose', $purpose)
            ->latest('created_at')
            ->first();

        return $record && $record->verified_at && now()->lessThanOrEqualTo($record->expires_at);
    }

    public function clear(string $email, string $purpose): void
    {
        DB::table('tbl_verification_codes')
            ->where('email_address', $email)
            ->where('purpose', $purpose)
            ->delete();

        RateLimiter::clear($this->attemptKey($email, $purpose));
    }

    public function nextCooldownSeconds(string $email, string $purpose): int
    {
        $count = DB::table('tbl_verification_codes')
            ->where('email_address', $email)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $index = min($count - 1, count($this->cooldownSchedule) - 1);

        return $this->cooldownSchedule[$index];
    }
}