<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BillPayment;
use App\Services\FaturaApiService;
use App\Services\BillPaymentManager;
use App\Notifications\BillPaymentNotification;

class CheckPendingBillsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:check-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of pending bill payments using the Fatura API';

    /**
     * Execute the console command.
     */
    public function handle(FaturaApiService $apiService, BillPaymentManager $manager)
    {
        $pendingBills = BillPayment::where('api_status', 'pending')->get();

        if ($pendingBills->isEmpty()) {
            $this->info('No pending bills found.');
            return;
        }

        foreach ($pendingBills as $bill) {
            $response = $apiService->faturaKontrol($bill->tahsilat_api_islem_id);

            if ($response['success']) {
                $statusCode = $response['status_code'];
                $message = $response['message'];

                if ($statusCode === '2') {
                    // Success / Approved
                    $bill->update([
                        'api_status' => 'completed',
                        'api_status_message' => $message,
                    ]);
                    $bill->user->notify(new BillPaymentNotification($bill, 'completed'));
                    $this->info("Bill {$bill->id} completed.");
                } elseif ($statusCode === '3') {
                    // Failed / Cancelled
                    $manager->refundBill($bill, 'فشل من المزود: ' . $message);
                    $bill->user->notify(new BillPaymentNotification($bill, 'failed'));
                    $this->info("Bill {$bill->id} failed and refunded.");
                } else {
                    // 1: Pending, do nothing
                    $this->info("Bill {$bill->id} is still pending.");
                }
            } else {
                $this->error("Failed to check bill {$bill->id}: " . $response['message']);
            }
        }
    }
}
