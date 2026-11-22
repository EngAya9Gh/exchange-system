<?php

namespace App\Livewire\Sim\Concerns;

use App\Models\ActivityLog;
use App\Services\KontorApiService;
use App\Services\WhatsApp\SimOrderGroupNotifier;
use Illuminate\Support\Facades\Log;

/**
 * Emergency switch for automatic loading, one per supplier (SIM managers only).
 * Paused: new orders skip that supplier; with no other active supplier they are still charged and go to the
 * "Tele CARD" WhatsApp group for a manual load. Loads already sent keep being followed up (sims:check-api, «تحقق الآن»).
 */
trait ControlsAutoSend
{
    public function pauseAutoSend(string $supplier = KontorApiService::SUPPLIER_KONTOR86): void
    {
        $this->switchAutoSend($supplier, false);
    }

    public function resumeAutoSend(string $supplier = KontorApiService::SUPPLIER_KONTOR86): void
    {
        $this->switchAutoSend($supplier, true);
    }

    protected function switchAutoSend(string $supplier, bool $enabled): void
    {
        abort_unless((bool) auth()->user()?->can('manage_sims'), 403);
        abort_unless(in_array($supplier, KontorApiService::SUPPLIERS, true), 404);

        $api = KontorApiService::for($supplier);
        $user = auth()->user();
        $flash = $enabled ? 'messages.sim_auto_send_resumed_flash' : 'messages.sim_auto_send_paused_flash';

        // Two managers clicking at once: the second click changes nothing and posts nothing
        if ($api->isAutoSendPaused() !== $enabled) {
            session()->flash('auto_send_flash', __($flash, ['supplier' => $supplier]));
            return;
        }

        $api->setAutoSendPaused(!$enabled, $user);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $enabled ? 'sim_auto_send_resumed' : 'sim_auto_send_paused',
            'description' => ($enabled ? 'استئناف' : 'إيقاف') . " التحميل التلقائي للباقات عبر {$supplier}",
            'ip_address' => request()->ip(),
        ]);

        // While this one is paused, new orders go to the next active supplier (if any)
        $nowVia = null;
        if (!$enabled) {
            foreach (KontorApiService::inPriorityOrder() as $other => $otherApi) {
                if ($other !== $supplier && $otherApi->autoSendActive()) {
                    $nowVia = $other;
                    break;
                }
            }
        }

        try {
            app(SimOrderGroupNotifier::class)->sendAutoSendNotice($supplier, !$enabled, $user->name, $nowVia);
        } catch (\Throwable $e) {
            Log::error('Failed to post the auto-send switch to the SIM group: ' . $e->getMessage());
        }

        session()->flash('auto_send_flash', __($flash, ['supplier' => $supplier]));
    }
}
