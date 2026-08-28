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

            // PayStore's BillPayment flow is session-based:
            // BillInquiry response includes Set-Cookie → must be sent back with BillPayment
            if ($operation === 'BillPayment') {
                $inquiryId = $data['request']['BillInquiryId'] ?? null;
                $companyCode = $data['request']['CompanyCode'] ?? null;
                $cookieFound = false;

                // Try primary key: by BillInquiryId
                if ($inquiryId) {
                    $cacheKey = 'paystore_cookie_' . $inquiryId;
                    $cookies = \Illuminate\Support\Facades\Cache::pull($cacheKey); // pull = get + forget
                    if ($cookies) {
                        $cookieFound = true;
                    }
                }

                // Fallback key: by CompanyCode (in case BillInquiryId differs between root and BillList)
                if (!$cookieFound && $companyCode) {
                    $fallbackKey = 'paystore_cookie_company_' . $companyCode;
                    $cookies = \Illuminate\Support\Facades\Cache::pull($fallbackKey);
                    if ($cookies) {
                        $cookieFound = true;
                        Log::channel('daily')->info("BillPayment: Cookie found via fallback company key (CompanyCode={$companyCode})");
                    }
                }

                if ($cookieFound && $cookies) {
                    $cookieArray = is_array($cookies) ? $cookies : [$cookies];
                    $cookieHeader = implode('; ', array_map(function($c) { return explode(';', $c)[0]; }, $cookieArray));
                    $request = $request->withHeaders(['Cookie' => $cookieHeader]);
                } else {
                    Log::warning("BillPayment: No cached cookie found for BillInquiryId={$inquiryId}, CompanyCode={$companyCode}. PayStore may reject with 0188.");
                }
            }

            $response = $request->asForm()->post($this->baseUrl, $data);

            $responseBody = $response->body();
            $responseJson = $response->json() ?? [];

            Log::channel('daily')->info("=== Fatura API Response [{$operation}] ===", [
                'status'   => $response->status(),
                'body_raw' => $responseBody,
                'body_json'=> $responseJson,
                'sent_cookie' => $cookieHeader,
                'received_cookies' => $response->headers()['Set-Cookie'] ?? null,
            ]);

            if ($response->successful()) {
                // Capture session cookie from BillInquiry to use in subsequent BillPayment
                if ($operation === 'BillInquiry') {
                    $setCookie = $response->headers()['Set-Cookie'] ?? null;
                    if ($setCookie) {
                        $inquiryResult = $responseJson['BillInquiryResult'] ?? $responseJson;
                        $rootInquiryId = $inquiryResult['BillInquiryId'] ?? null;
                        $companyCode = $requestData['CompanyCode'] ?? null;
                        $cachedIds = [];

                        // Cache under root BillInquiryId
                        if ($rootInquiryId) {
                            \Illuminate\Support\Facades\Cache::put('paystore_cookie_' . $rootInquiryId, $setCookie, now()->addMinutes(30));
                            $cachedIds[] = $rootInquiryId;
                        }

                        // Cache under each bill's BillInquiryId (may differ from root)
                        $billList = $inquiryResult['BillList'] ?? [];
                        if (is_array($billList)) {
                            // Normalize single bill to array
                            if (!empty($billList) && !isset($billList[0])) {
                                $billList = [$billList];
                            }
                            foreach ($billList as $bill) {
                                $billInqId = $bill['BillInquiryId'] ?? null;
                                if ($billInqId && !in_array($billInqId, $cachedIds)) {
                                    \Illuminate\Support\Facades\Cache::put('paystore_cookie_' . $billInqId, $setCookie, now()->addMinutes(30));
                                    $cachedIds[] = $billInqId;
                                }
                            }
                        }

                        // Fallback: cache under CompanyCode for extra reliability
                        if ($companyCode) {
                            \Illuminate\Support\Facades\Cache::put('paystore_cookie_company_' . $companyCode, $setCookie, now()->addMinutes(30));
                        }

                        Log::channel('daily')->info("BillInquiry: Cached session cookie", [
                            'cached_inquiry_ids' => $cachedIds,
                            'company_code' => $companyCode,
                        ]);
                    } else {
                        Log::warning("BillInquiry: No Set-Cookie header received from PayStore");
                    }
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
