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
            $response = Http::withOptions(['verify' => false])->asForm()->post($this->baseUrl, $data);

            $responseBody = $response->body();
            $responseJson = $response->json() ?? [];

            Log::channel('daily')->info("=== Fatura API Response [{$operation}] ===", [
                'status'   => $response->status(),
                'body_raw' => $responseBody,
                'body_json'=> $responseJson,
            ]);

            if ($response->successful()) {
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
                'BillOrderNumber' => (string) ($data['fatura_no'] ?? ''),
            ]);
            
            $resData = $result['BillPaymentResult'] ?? [];
        } else {
            $result = $this->sendRequest('OwnBillPayment', [
                'CompanyCode'     => (string) $data['kurum_id'],
                'TransactionId'   => (string) $data['tahsilat_api_islem_id'],
                'CustomerCode'    => $data['abone_no'],
                'BillOrderNumber' => $data['fatura_no'] ?? '',
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
