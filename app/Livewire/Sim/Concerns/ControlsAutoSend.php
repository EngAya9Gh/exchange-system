<?php

namespace App\Livewire\Sim\Concerns;

use App\Models\ActivityLog;
use App\Services\KontorApiService;
use App\Services\WhatsApp\SimOrderGroupNotifier;
use Illuminate\Support\Facades\Log;

/**
 * Emergency switch for automatic loading through kontor86 (SIM managers only).
 * Paused: new orders are still charged, but go to the "Tele CARD" WhatsApp group for a manual load
 * instead of the supplier. Loads already sent keep being followed up (sims:check-api, «تحقق الآن»).
 */
trait ControlsAutoSend
{
    public function pauseAutoSend(): void
    {
        $this->switchAutoSend(false);
    }

    public function resumeAutoSend(): void
    {
        $this->switchAutoSend(true);
    }

    protected function switchAutoSend(bool $enabled): void
    {
        abort_unless((bool) auth()->user()?->can('manage_sims'), 403);

        $api = app(KontorApiService::class);
        $user = auth()->user();
        $flash = $enabled ? 'messages.sim_auto_send_resumed_flash' : 'messages.sim_auto_send_paused_flash';

        // Two managers clicking at once: the second click changes nothing and posts nothing
        if ($api->isAutoSendPaused() !== $enabled) {
            session()->flash('auto_send_flash', __($flash));
            return;
        }

        $api->setAutoSendPaused(!$enabled, $user);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $enabled ? 'sim_auto_send_resumed' : 'sim_auto_send_paused',
            'description' => ($enabled ? 'استئناف' : 'إيقاف') . ' التحميل التلقائي للباقات عبر kontor86',
            'ip_address' => request()->ip(),
        ]);

        try {
            app(SimOrderGroupNotifier::class)->sendAutoSendNotice(!$enabled, $user->name);
        } catch (\Throwable $e) {
            Log::error('Failed to post the auto-send switch to the SIM group: ' . $e->getMessage());
        }

        session()->flash('auto_send_flash', __($flash));
    }
}
