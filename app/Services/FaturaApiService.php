<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaturaApiService
{
    protected string $baseUrl;
    protected string $dealerCode;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl = \App\Models\Setting::where('key', 'FATURA_API_URL')->value('value') ?? env('FATURA_API_URL', 'http://bayi.bayiwebpanel.tech/ClientWebService');
        $this->dealerCode = \App\Models\Setting::where('key', 'FATURA_API_DEALER_CODE')->value('value') ?? env('FATURA_API_DEALER_CODE', '');
        $this->username = \App\Models\Setting::where('key', 'FATURA_API_USERNAME')->value('value') ?? env('FATURA_API_USERNAME', '');
        $this->password = \App\Models\Setting::where('key', 'FATURA_API_PASSWORD')->value('value') ?? env('FATURA_API_PASSWORD', '');
    }

    /**
     * Get the base authentication request parameters.
     */
    protected function getBaseRequest(): array
    {
        return [
            'DealerCode' => $this->dealerCode,
            'Username' => $this->username,
            'Password' => $this->password,
        ];
    }

    /**
     * Send a POST request to the API.
     */
    protected function sendRequest(string $operation, array $requestData = []): array
    {
        $data = [
            'Operation' => $operation,
            'request' => array_merge($this->getBaseRequest(), $requestData)
        ];

        Log::channel('daily')->info("=== Fatura API Request [{$operation}] ===", [
            'url'       => $this->baseUrl,
            'operation' => $operation,
            'payload'   => $data,
        ]);

        try {
            $request = Http::withOptions(['verify' => false]);
            $cookieHeader = null;

            // Cookie storage directory (file-based, bypasses Cache driver issues)
            $cookieDir = storage_path('app/paystore_cookies');
            if (!is_dir($cookieDir)) {
                @mkdir($cookieDir, 0755, true);
            }

            // PayStore's BillPayment flow is session-based:
            // BillInquiry response includes Set-Cookie → must be sent back with BillPayment
            if ($operation === 'BillPayment') {
                $inquiryId = $data['request']['BillInquiryId'] ?? null;
                $companyCode = $data['request']['CompanyCode'] ?? null;
                $cookieData = null;

                // Try primary key: by BillInquiryId
                if ($inquiryId) {
                    $cookieFile = $cookieDir . '/inquiry_' . $inquiryId . '.json';
                    if (file_exists($cookieFile)) {
                        $cookieData = json_decode(file_get_contents($cookieFile), true);
                        @unlink($cookieFile); // one-time use
                        Log::channel('daily')->info("BillPayment: Cookie loaded from file for InquiryId={$inquiryId}");
                    }
                }

                // Fallback key: by CompanyCode
                if (!$cookieData && $companyCode) {
                    $cookieFile = $cookieDir . '/company_' . $companyCode . '.json';
                    if (file_exists($cookieFile)) {
                        $cookieData = json_decode(file_get_contents($cookieFile), true);
                        @unlink($cookieFile);
                        Log::channel('daily')->info("BillPayment: Cookie loaded from fallback company file (CompanyCode={$companyCode})");
                    }
                }

                if ($cookieData && !empty($cookieData['cookies'])) {
                    $cookieArray = $cookieData['cookies'];
                    $cookieHeader = implode('; ', array_map(function($c) { return explode(';', $c)[0]; }, $cookieArray));
                    $request = $request->withHeaders(['Cookie' => $cookieHeader]);
                    Log::channel('daily')->info("BillPayment: Sending cookie header", ['cookie' => $cookieHeader]);
                } else {
                    Log::warning("BillPayment: No cookie file found for BillInquiryId={$inquiryId}, CompanyCode={$companyCode}. PayStore may reject with 0188.");
                }
            }

            $response = $request->asForm()->post($this->baseUrl, $data);

            $responseBody = $response->body();
            $responseJson = $response->json() ?? [];

            // Use PSR-7 getHeader() for case-insensitive header access
            $setCookieValues = $response->getHeader('Set-Cookie');

            Log::channel('daily')->info("=== Fatura API Response [{$operation}] ===", [
                'status'            => $response->status(),
                'body_raw'          => $responseBody,
                'body_json'         => $responseJson,
                'sent_cookie'       => $cookieHeader,
                'set_cookie_values' => $setCookieValues,
                'all_header_keys'   => array_keys($response->headers()),
            ]);

            if ($response->successful()) {
                // Save session cookie from BillInquiry to files for BillPayment to use
                if ($operation === 'BillInquiry' && !empty($setCookieValues)) {
                    try {
                        $inquiryResult = $responseJson['BillInquiryResult'] ?? $responseJson;
                        $rootInquiryId = $inquiryResult['BillInquiryId'] ?? null;
                        $companyCode = $requestData['CompanyCode'] ?? null;
                        $savedFiles = [];
                        $cookiePayload = json_encode(['cookies' => $setCookieValues, 'time' => now()->toIso8601String()]);

                        // Save under root BillInquiryId
                        if ($rootInquiryId) {
                            file_put_contents($cookieDir . '/inquiry_' . $rootInquiryId . '.json', $cookiePayload);
                            $savedFiles[] = 'inquiry_' . $rootInquiryId;
                        }

                        // Save under each bill's BillInquiryId (may differ from root)
                        $billList = $inquiryResult['BillList'] ?? [];
                        if (is_array($billList)) {
                            if (!empty($billList) && !isset($billList[0])) {
                                $billList = [$billList];
                            }
                            foreach ($billList as $bill) {
                                $billInqId = $bill['BillInquiryId'] ?? null;
                                if ($billInqId && $billInqId !== $rootInquiryId) {
                                    file_put_contents($cookieDir . '/inquiry_' . $billInqId . '.json', $cookiePayload);
                                    $savedFiles[] = 'inquiry_' . $billInqId;
                                }
                            }
                        }

                        // Fallback: save under CompanyCode
                        if ($companyCode) {
                            file_put_contents($cookieDir . '/company_' . $companyCode . '.json', $cookiePayload);
                            $savedFiles[] = 'company_' . $companyCode;
                        }

                        Log::channel('daily')->info("BillInquiry: ✅ Session cookie saved to files", [
                            'saved_files' => $savedFiles,
                            'cookie_dir' => $cookieDir,
                            'cookie_count' => count($setCookieValues),
                        ]);
                    } catch (\Exception $fileEx) {
                        Log::error("BillInquiry: ❌ Failed to save cookie to file: " . $fileEx->getMessage());
                    }
                } elseif ($operation === 'BillInquiry') {
                    Log::warning("BillInquiry: ⚠️ No Set-Cookie header received from PayStore", [
                        'all_header_keys' => array_keys($response->headers()),
                    ]);
                }
                return $responseJson;
            }

            Log::error("Fatura API {$operation} failed: " . $response->status());
            return ['ResponseCode' => 'ERROR', 'Message' => 'HTTP Error ' . $response->status()];
        } catch (\Exception $e) {
            Log::error("Fatura API {$operation} exception: " . $e->getMessage());
            return ['ResponseCode' => 'EXCEPTION', 'Message' => $e->getMessage()];
        }
    }

    /**
     * Fetch the list of institutions (Kurumlar)
     */
    public function kurumListesi(): array
    {
        $result = $this->sendRequest('CompanyList');
        
        if (isset($result['CompanyListResult']['CompanyList']) && is_array($result['CompanyListResult']['CompanyList'])) {
            return $result['CompanyListResult']['CompanyList'];
        }

        return [];
    }

    /**
     * Bill Inquiry (Fatura Sorgulama)
     */
    public function sorgula(int|string $kurumId, string $aboneNo): array
    {
        Log::channel('daily')->info("=== sorgula() called ===", [
            'kurum_id' => $kurumId,
            'abone_no' => $aboneNo,
        ]);

        $result = $this->sendRequest('BillInquiry', [
            'CompanyCode' => (string) $kurumId,
            'CustomerCode' => $aboneNo,
        ]);

        $parsed = $result['BillInquiryResult'] ?? $result;

        Log::channel('daily')->info("=== sorgula() parsed result ===", $parsed);

        return $parsed;
    }

    /**
     * Submit a bill for payment
     */
    public function faturaEkle(array $data): array
    {
        // BillInquiryId "0" means the API doesn't support BillPayment for this institution,
        // so we must use OwnBillPayment. Only use BillPayment when BillInquiryId is a real non-zero value.
        $billInquiryId = $data['bill_inquiry_id'] ?? null;
        $useBillPayment = !empty($billInquiryId) && $billInquiryId !== '0' && $billInquiryId !== 0;

        if ($useBillPayment) {
            $result = $this->sendRequest('BillPayment', [
                'BillInquiryId'   => (string) $billInquiryId,
                'TransactionId'   => (string) $data['tahsilat_api_islem_id'],
                'CompanyCode'     => (string) ($data['kurum_id'] ?? ''),
            ]);
            
            $resData = $result['BillPaymentResult'] ?? [];
        } else {
            $result = $this->sendRequest('OwnBillPayment', [
                'CompanyCode'     => (string) $data['kurum_id'],
                'TransactionId'   => (string) $data['tahsilat_api_islem_id'],
                'CustomerCode'    => $data['abone_no'],
                'BillAmount'      => $data['amount'],
                'DueDate'         => $data['son_odeme_tarihi'] ?? date('Y-m-d'),
            ]);
            
            // OwnBillPayment returns OwnBillPaymentResult (NOT BillPaymentResult)
            $resData = $result['OwnBillPaymentResult'] ?? $result['BillPaymentResult'] ?? [];
        }

        Log::channel('daily')->info('=== faturaEkle() resData ===', $resData);

        $code = $resData['ResponseCode'] ?? 'ERROR';
        $success = ($code === '0000' || $code === '000');

        // Code 0110 = Insufficient API balance (provider account needs top-up)
        $isInsufficientApiBalance = ($code === '0110');
        if ($isInsufficientApiBalance) {
            Log::channel('daily')->critical('=== API BALANCE CRITICAL: Insufficient funds in BayiWebPanel account ===', [
                'response_code' => $code,
                'message_en'   => $resData['Message_EN'] ?? '',
                'message_tr'   => $resData['Message_TR'] ?? '',
                'kurum_id'     => $data['kurum_id'] ?? '',
                'amount'       => $data['amount'] ?? '',
            ]);
        }
        
        return [
            'success'                   => $success,
            'cost'                      => $resData['Cost'] ?? 0, 
            'remaining_balance'         => 0,
            'total_deducted'            => $data['amount'],
            'message'                   => $resData['Message_TR'] ?? 'Unknown response',
            'message_en'                => $resData['Message_EN'] ?? '',
            'error_code'                => $code,
            'api_payment_id'            => $resData['BillPaymentId'] ?? null,
            'insufficient_api_balance'  => $isInsufficientApiBalance,
        ];
    }

    /**
     * Check the status of a specific transaction
     */

    public function faturaKontrol(string $tahsilatApiIslemId): array
    {
        return [
            'success' => false,
            'message' => 'Status check not explicitly supported in PayStore docs for bills.',
        ];
    }

    /**
     * Get the deposit / balance
     */
    public function getDeposit(): array
    {
        $result = $this->sendRequest('GetDeposit');
        return $result['GetDepositResult'] ?? [];
    }
}
