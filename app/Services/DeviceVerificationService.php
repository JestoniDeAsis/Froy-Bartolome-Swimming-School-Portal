<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * New-device login verification.
 *
 *  - Recognized browsers: a random token in a long-lived cookie; only its
 *    SHA-256 hash is stored (tbl_user_devices).
 *  - Email codes: 6 digits, stored hashed in the cache for CODE_TTL_MINUTES,
 *    with limits on wrong guesses and on how often a code can be sent.
 */
class DeviceVerificationService
{
    public const DEVICES_TABLE        = 'tbl_user_devices';
    public const DEVICE_LIFETIME_DAYS = 365;

    public const CODE_TTL_MINUTES    = 10;
    public const MAX_WRONG_CODES     = 5;    // per user, per CODE_TTL window
    public const RESEND_COOLDOWN     = 60;   // seconds between codes
    public const MAX_CODES_PER_HOUR  = 5;

    /* ---------------------------------------------------------
     * Recognized devices
     * --------------------------------------------------------- */

    // One cookie per user, so two people sharing a computer each keep their own.
    private function cookieName(int $userId): string
    {
        return 'fbss_device_' . $userId;
    }

    public function isRecognized(Request $request, int $userId): bool
    {
        $token = $request->cookie($this->cookieName($userId));

        if (!is_string($token) || $token === '') {
            return false;
        }

        return DB::table(self::DEVICES_TABLE)
            ->where('user_id', $userId)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->exists();
    }

    /** Saves a new device and returns the cookie to attach to the response. */
    public function remember(Request $request, int $userId): Cookie
    {
        // Housekeeping: drop expired devices.
        DB::table(self::DEVICES_TABLE)->where('expires_at', '<', now())->delete();

        $token = Str::random(64);

        DB::table(self::DEVICES_TABLE)->insert([
            'user_id'      => $userId,
            'token_hash'   => hash('sha256', $token),
            'user_agent'   => Str::limit((string) $request->userAgent(), 255, ''),
            'ip_address'   => $request->ip(),
            'created_at'   => now(),
            'last_used_at' => now(),
            'expires_at'   => now()->addDays(self::DEVICE_LIFETIME_DAYS),
        ]);

        return $this->makeCookie($request, $userId, $token);
    }

    /** A recognized device logged in again: bump last use and push the expiry out. */
    public function touch(Request $request, int $userId): Cookie
    {
        $token = (string) $request->cookie($this->cookieName($userId));

        DB::table(self::DEVICES_TABLE)
            ->where('user_id', $userId)
            ->where('token_hash', hash('sha256', $token))
            ->update([
                'last_used_at' => now(),
                'expires_at'   => now()->addDays(self::DEVICE_LIFETIME_DAYS),
            ]);

        return $this->makeCookie($request, $userId, $token);
    }

    private function makeCookie(Request $request, int $userId, string $token): Cookie
    {
        return cookie(
            $this->cookieName($userId),
            $token,
            self::DEVICE_LIFETIME_DAYS * 24 * 60,   // minutes
            '/',
            null,
            $request->isSecure(),   // Secure flag whenever the site is on HTTPS
            true,                   // HttpOnly
            false,
            'lax'
        );
    }

    /* ---------------------------------------------------------
     * Email codes
     * --------------------------------------------------------- */

    private function codeKey(int $userId): string
    {
        return 'device-code|' . $userId;
    }

    public function hasActiveCode(int $userId): bool
    {
        return Cache::has($this->codeKey($userId));
    }

    /** @return array{allowed: bool, wait_seconds: int} */
    public function canSend(int $userId): array
    {
        $limits = [
            'device-send-hour|'     => self::MAX_CODES_PER_HOUR,
            'device-send-cooldown|' => 1,
        ];

        foreach ($limits as $prefix => $max) {
            if (RateLimiter::tooManyAttempts($prefix . $userId, $max)) {
                return ['allowed' => false, 'wait_seconds' => RateLimiter::availableIn($prefix . $userId)];
            }
        }

        return ['allowed' => true, 'wait_seconds' => 0];
    }

    /** Creates a code (stored hashed) and returns the plain code to email. */
    public function issue(int $userId): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->codeKey($userId), Hash::make($code), now()->addMinutes(self::CODE_TTL_MINUTES));

        RateLimiter::hit('device-send-hour|' . $userId, 3600);
        RateLimiter::hit('device-send-cooldown|' . $userId, self::RESEND_COOLDOWN);

        return $code;
    }

    public function tooManyWrongCodes(int $userId): bool
    {
        return RateLimiter::tooManyAttempts('device-verify|' . $userId, self::MAX_WRONG_CODES);
    }

    public function wrongCodeWaitSeconds(int $userId): int
    {
        return RateLimiter::availableIn('device-verify|' . $userId);
    }

    /** Checks a code. A wrong guess counts toward the limit; after too many the code is thrown away. */
    public function check(int $userId, string $code): bool
    {
        $hash = Cache::get($this->codeKey($userId));

        if (!is_string($hash) || !Hash::check($code, $hash)) {
            RateLimiter::hit('device-verify|' . $userId, self::CODE_TTL_MINUTES * 60);

            if ($this->tooManyWrongCodes($userId)) {
                $this->forgetCode($userId);
            }

            return false;
        }

        $this->clear($userId);

        return true;
    }

    public function forgetCode(int $userId): void
    {
        Cache::forget($this->codeKey($userId));
    }

    public function clear(int $userId): void
    {
        $this->forgetCode($userId);
        RateLimiter::clear('device-verify|' . $userId);
    }
}