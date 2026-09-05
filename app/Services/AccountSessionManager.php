<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSessionManager
{
    public function sessionsFor(
        User $user,
        string $currentSessionId,
        ?string $currentIpAddress = null,
        ?string $currentUserAgent = null,
    ): array {
        $sessions = DB::table($this->table())
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => $this->presentSession(
                id: (string) $session->id,
                ipAddress: $session->ip_address,
                userAgent: $session->user_agent,
                lastActivity: (int) $session->last_activity,
                currentSessionId: $currentSessionId,
            ))
            ->values()
            ->all();

        $hasCurrentSession = collect($sessions)
            ->contains(fn (array $session) => $session['is_current']);

        if (! $hasCurrentSession) {
            array_unshift($sessions, $this->presentSession(
                id: $currentSessionId,
                ipAddress: $currentIpAddress,
                userAgent: $currentUserAgent,
                lastActivity: now()->timestamp,
                currentSessionId: $currentSessionId,
            ));
        }

        return $sessions;
    }

    public function revokeSession(
        User $user,
        string $sessionKey,
        string $currentSessionId,
    ): bool {
        $session = DB::table($this->table())
            ->where('user_id', $user->getAuthIdentifier())
            ->get(['id'])
            ->first(fn ($session) => hash_equals(
                $this->fingerprint((string) $session->id),
                $sessionKey,
            ));

        if (! $session) {
            return false;
        }

        $sessionId = (string) $session->id;

        if (hash_equals($currentSessionId, $sessionId)) {
            return false;
        }

        $deleted = DB::table($this->table())
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', $sessionId)
            ->delete();

        if ($deleted > 0) {
            $this->invalidateRememberedLogins($user);
        }

        return $deleted > 0;
    }

    public function revokeOtherSessions(User $user, string $currentSessionId): int
    {
        $deleted = DB::table($this->table())
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', $currentSessionId)
            ->delete();

        if ($deleted > 0) {
            $this->invalidateRememberedLogins($user);
        }

        return $deleted;
    }

    public function fingerprint(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }

    private function presentSession(
        string $id,
        ?string $ipAddress,
        ?string $userAgent,
        int $lastActivity,
        string $currentSessionId,
    ): array {
        $browser = $this->browserName($userAgent);
        $platform = $this->platformName($userAgent);

        return [
            'key' => $this->fingerprint($id),
            'is_current' => hash_equals($currentSessionId, $id),
            'ip_address' => $ipAddress ?: 'IP no disponible',
            'browser' => $browser,
            'platform' => $platform,
            'device_label' => $browser.' en '.$platform,
            'last_active_at' => Carbon::createFromTimestamp($lastActivity),
        ];
    }

    private function browserName(?string $userAgent): string
    {
        $userAgent ??= '';

        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') || str_contains($userAgent, 'CriOS/') => 'Google Chrome',
            (str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/')) => 'Safari',
            default => 'Navegador desconocido',
        };
    }

    private function platformName(?string $userAgent): string
    {
        $userAgent ??= '';

        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'dispositivo desconocido',
        };
    }

    private function invalidateRememberedLogins(User $user): void
    {
        $user->setRememberToken(Str::random(60));
        $user->saveQuietly();
    }

    private function table(): string
    {
        return (string) config('session.table', 'sessions');
    }
}
