<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\BillPayment;
use App\Services\FaturaApiService;
use App\Services\BillPaymentManager;
use Illuminate\Support\Facades\Log;

class BillPaymentsDashboard extends Component
{
    use WithPagination;

    public $categorizedKurumlar = [];
    public $selectedCategory = '';
    public $selectedKurumId = null;
    public $aboneNo = '';
    
    // For Sorgula result
    public $queriedName = '';
    public $isQueried = false;
    public $dueBills = []; // Array of bills returned from API
    
    // API Balance (Visible to Super Admins)
    public $apiBalance = null;

    // Filters
    public $statusFilter = 'all';
    public $searchQuery = '';

    protected function rules()
    {
        return [
            'selectedKurumId' => 'required',
            'aboneNo' => 'required|string|min:3',
        ];
    }

    public function mount(FaturaApiService $apiService)
    {
        $kurumlar = $apiService->kurumListesi();
        $this->categorizedKurumlar = $this->categorizeKurumlar($kurumlar);
        
        if (!empty($this->categorizedKurumlar)) {
            $this->selectedCategory = array_key_first($this->categorizedKurumlar);
        }

        // Fetch API Balance for Super Admins
        if (auth()->check() && auth()->user()->hasRole('Super Admin')) {
            $depositResult = $apiService->getDeposit();
            // Expected response format usually has 'Balance' or 'Amount'
            // We need to parse whatever PayStore returns. For now we will store the raw response or try to parse it.
            // "GetDepositResult" typically contains "Balance" or similar.
            // Example: [ "Balance" => "1500.00", "ResponseCode" => "0000" ]
            $this->apiBalance = $depositResult['Deposit'] ?? $depositResult['Balance'] ?? 'غير متوفر';
        }
    }

    protected function categorizeKurumlar(array $kurumlar): array
    {
        $categories = [
            'ÖZEL TV ÖDEMELERİ'       => [],
            'İSTANBUL KURUMLARI'       => [],
            'ADSL İNTERNET TAHSİLATI' => [],
            'GSM TELEKOM TAHSİLATI'   => [],
            'DOĞALGAZ TAHSİLATI'      => [],
            'ELEKTİRİK TAHSİLATI'    => [],
            'SU TAHSİLATI'            => [],
            'KURUM ÖDEMELERİ'         => [],
        ];

        // Hardcoded mapping: CompanyCode => category (or multiple categories via array)
        // Based on the official BayiWebPanel website tabs shown in the provided images
        $codeMap = [
            // ── ÖZEL TV ÖDEMELERİ ──────────────────
            65  => ['ÖZEL TV ÖDEMELERİ'],                    // D-SMART TV
            8   => ['ÖZEL TV ÖDEMELERİ'],                    // DİGİTURK
            12  => ['ÖZEL TV ÖDEMELERİ'],                    // TÜRKSAT - TV

            // ── İSTANBUL KURUMLARI ─────────────────
            116 => ['İSTANBUL KURUMLARI'],                   // İSTANBUL AESAŞ (AYEDAŞ ENERJİSA)
            115 => ['İSTANBUL KURUMLARI'],                   // İSTANBUL BEDAŞ(CK) = CK BEDAŞ BANKA
            101 => ['İSTANBUL KURUMLARI'],                   // ÇORUH EDAŞ AKSA ENERJİ = CK BOĞAZİÇİ
            17  => ['İSTANBUL KURUMLARI'],                   // İGDAŞ
            16  => ['İSTANBUL KURUMLARI'],                   // İSKİ İSTANBUL SU

            // ── ADSL İNTERNET TAHSİLATI ────────────
            9   => ['ADSL İNTERNET TAHSİLATI'],              // D-SMART SMİLE ADSL
            24  => ['ADSL İNTERNET TAHSİLATI'],              // MİLLENİCOM DOPİNG
            10  => ['ADSL İNTERNET TAHSİLATI'],              // TURKCELL SUPERONLİNE
            38  => ['ADSL İNTERNET TAHSİLATI'],              // TURKNET
            93  => ['ADSL İNTERNET TAHSİLATI'],              // TÜRKTELEKOM İNTERNET
            407 => ['ADSL İNTERNET TAHSİLATI'],              // ATLANTİS NET
            344 => ['ADSL İNTERNET TAHSİLATI'],              // GÖKNET
            410 => ['ADSL İNTERNET TAHSİLATI'],              // MEKROTİK İNTERNET
            305 => ['ADSL İNTERNET TAHSİLATI'],              // ORİS TELEKOM
            23  => ['ADSL İNTERNET TAHSİLATI'],              // VODAFONE KOÇNET

            // ── GSM TELEKOM TAHSİLATI ──────────────
            2   => ['GSM TELEKOM TAHSİLATI'],                // TURKCELL
            1   => ['GSM TELEKOM TAHSİLATI'],                // TÜRK TELEKOM EV-İŞ
            4   => ['GSM TELEKOM TAHSİLATI'],                // TÜRKTELEKOM MOBİL
            3   => ['GSM TELEKOM TAHSİLATI'],                // VODAFONE
            92  => ['GSM TELEKOM TAHSİLATI'],                // TTNET MOBİLE

            // ── DOĞALGAZ TAHSİLATI ────────────────
            53  => ['DOĞALGAZ TAHSİLATI'],                   // ADAPAZARI GAZ (SAKARYA)
            306 => ['DOĞALGAZ TAHSİLATI'],                   // AKSA ADANA GAZ
            315 => ['DOĞALGAZ TAHSİLATI'],                   // AKSA MALATYA GAZ
            348 => ['DOĞALGAZ TAHSİLATI'],                   // AKSA TOKAT AMASYA GAZ
            160 => ['DOĞALGAZ TAHSİLATI'],                   // AKSA VAN GAZ
            405 => ['DOĞALGAZ TAHSİLATI'],                   // AKMERCAN SİNOP
            18  => ['DOĞALGAZ TAHSİLATI'],                   // ANKARA GAZ EGO
            6   => ['DOĞALGAZ TAHSİLATI'],                   // BURSA GAZ
            397 => ['DOĞALGAZ TAHSİLATI'],                   // ENERYA ANTALYAGAZ
            31  => ['DOĞALGAZ TAHSİLATI'],                   // ENERYA KAPADOKYA GAZ
            28  => ['DOĞALGAZ TAHSİLATI'],                   // ENERYA KONYA GAZNET
            50  => ['DOĞALGAZ TAHSİLATI'],                   // ESKİŞEHİR ESGAZ
            21  => ['DOĞALGAZ TAHSİLATI'],                   // İZMİR GAZ
            29  => ['DOĞALGAZ TAHSİLATI'],                   // İZMİT GAZ
            85  => ['DOĞALGAZ TAHSİLATI'],                   // KAYSERİ DOĞALGAZ
            76  => ['DOĞALGAZ TAHSİLATI'],                   // KIRGAZ KIRŞEHİR
            27  => ['DOĞALGAZ TAHSİLATI'],                   // NETGAZ KONYA EREĞLİ
            75  => ['DOĞALGAZ TAHSİLATI'],                   // SAMGAZ SAMSUN
            51  => ['DOĞALGAZ TAHSİLATI'],                   // SÜRMELİ GAZ (YOZGAT)
            120 => ['DOĞALGAZ TAHSİLATI'],                   // TOROSGAZ GAZ
            394 => ['DOĞALGAZ TAHSİLATI'],                   // TRAKYA GAZ

            // ── ELEKTİRİK TAHSİLATI ───────────────
            328 => ['ELEKTİRİK TAHSİLATI'],                  // AKDENİZ ELEKTRİK
            20  => ['ELEKTİRİK TAHSİLATI'],                  // AYDEM ELEKTRİK
            67  => ['ELEKTİRİK TAHSİLATI'],                  // BAŞKENT(ANKARA ENERJİSA)
            129 => ['ELEKTİRİK TAHSİLATI'],                  // ÇAMLIBEL ELEKTRİK
            334 => ['ELEKTİRİK TAHSİLATI'],                  // DİCLE EDAŞ
            341 => ['ELEKTİRİK TAHSİLATI'],                  // ESKİŞEHİR OSMANGAZİ OEDAŞ(ZORLU)
            124 => ['ELEKTİRİK TAHSİLATI'],                  // FIRAT EDAŞ
            314 => ['ELEKTİRİK TAHSİLATI'],                  // GEDİZ ELEKTRİK
            87  => ['ELEKTİRİK TAHSİLATI'],                  // KAYSERİ ELEKTRİK
            303 => ['ELEKTİRİK TAHSİLATI'],                  // MERAM (MEPAŞ)
            70  => ['ELEKTİRİK TAHSİLATI'],                  // SEPAŞ ENERJİ
            318 => ['ELEKTİRİK TAHSİLATI'],                  // TOROSLAR ELEKTRİK
            138 => ['ELEKTİRİK TAHSİLATI'],                  // TREDAŞ ELEKTRİK
            122 => ['ELEKTİRİK TAHSİLATI'],                  // ULUDAĞ ELEKTRİK
            342 => ['ELEKTİRİK TAHSİLATI'],                  // VANGÖLÜ EDAŞ
            99  => ['ELEKTİRİK TAHSİLATI'],                  // YEŞİLIRMAK YEPAŞ
            333 => ['ELEKTİRİK TAHSİLATI'],                  // ZORLU ELEKTİRİK

            // ── SU TAHSİLATI ──────────────────────
            36  => ['SU TAHSİLATI'],                         // ADAPAZARI(SAKARYA) SU
            14  => ['SU TAHSİLATI'],                         // ANTALYA SU ASAT
            47  => ['SU TAHSİLATI'],                         // ASKİ ANKARA SU
            7   => ['SU TAHSİLATI'],                         // BURSA SU
            48  => ['SU TAHSİLATI'],                         // ESKİŞEHİR SU ESKİ
            44  => ['SU TAHSİLATI'],                         // GASKİ GAZİANTEP SU
            337 => ['SU TAHSİLATI'],                         // HATAY SU
            139 => ['SU TAHSİLATI'],                         // İZMİR SU
            30  => ['SU TAHSİLATI'],                         // İZMİT SU
            86  => ['SU TAHSİLATI'],                         // KASKİ KAYSERİ SU
            409 => ['SU TAHSİLATI'],                         // KIRŞEHİR SU
            13  => ['SU TAHSİLATI'],                         // KONYA SU
            109 => ['SU TAHSİLATI'],                         // MALATYA SU
            72  => ['SU TAHSİLATI'],                         // MASKİ MANİSA SU
            15  => ['SU TAHSİLATI'],                         // NEVŞEHİR SU
            45  => ['SU TAHSİLATI'],                         // SAMSUN SU
            310 => ['SU TAHSİLATI'],                         // SASKİ SAKARYA SU
            123 => ['SU TAHSİLATI'],                         // ŞANLIURFA SU
            134 => ['SU TAHSİLATI'],                         // TOKAT SU
            103 => ['SU TAHSİLATI'],                         // VAN SU
            336 => ['SU TAHSİLATI'],                         // YOZGAT SU

            // ── KURUM ÖDEMELERİ ──────────────────
            330 => ['KURUM ÖDEMELERİ'],                      // HGS YÜKLEME(PLAKA)
            91  => ['KURUM ÖDEMELERİ'],                      // MOTORLU TAŞITLAR VERGİSİ
            89  => ['KURUM ÖDEMELERİ'],                      // SSK CARİ DÖNEM
            369 => ['KURUM ÖDEMELERİ'],                      // SSK GEÇMİŞ DÖNEM
        ];

        foreach ($kurumlar as $kurum) {
            $rawName = $kurum['Name'] ?? $kurum['adi'] ?? '';
            $code    = (int)($kurum['CompanyCode'] ?? $kurum['id'] ?? 0);
            if (!$code) continue;

            $mappedKurum = ['id' => $code, 'name' => $rawName];

            if (isset($codeMap[$code])) {
                foreach ($codeMap[$code] as $cat) {
                    $categories[$cat][] = $mappedKurum;
                }
            } else {
                // Fallback: uncategorised goes to KURUM ÖDEMELERİ
                $categories['KURUM ÖDEMELERİ'][] = $mappedKurum;
            }
        }

        // Remove empty categories
        return array_filter($categories, fn($cat) => count($cat) > 0);
    }

    public function selectCategory($category)
    {
        $this->selectedCategory = $category;
        $this->selectedKurumId = null;
        $this->isQueried = false;
        $this->dueBills = [];
        $this->aboneNo = '';
        $this->queriedName = '';
    }

    public function selectKurum($kurumId)
    {
        // Reset query results when institution changes
        if ($this->selectedKurumId != $kurumId) {
            $this->isQueried = false;
            $this->dueBills = [];
            $this->queriedName = '';
            $this->aboneNo = '';
        }
        $this->selectedKurumId = $kurumId;
    }

    public function sorgula(FaturaApiService $apiService)
    {
        $this->validate();

        $result = $apiService->sorgula($this->selectedKurumId, $this->aboneNo);

        $responseCode = $result['ResponseCode'] ?? null;
        
        // PayStore API returns 0000 on success, or 000. 0105 means no debt.
        if ($responseCode === '0000' || $responseCode === '000' || $responseCode === '0105' || isset($result['BillList'])) {
            $this->queriedName = $result['CustomerName'] ?? 'Unknown';
            
            // Normalize due bills
            $bills = $result['BillList'] ?? [];
            if (!empty($bills)) {
                // If the first key is not numeric, it's a single associative array
                if (is_array($bills) && !isset($bills[0])) {
                    $this->dueBills = [$bills];
                } else {
                    $this->dueBills = $bills;
                }
            } else {
                // Fallback if the API returns bill details in the root result
                if (isset($result['Amount']) || isset($result['BillAmount']) || isset($result['BillOrderNumber'])) {
                    $this->dueBills = [$result];
                } else {
                    $this->dueBills = [];
                }
            }
            
            // If response is 0105, definitely no bills
            if ($responseCode === '0105') {
                $this->dueBills = [];
            }
            
            $this->isQueried = true;
            
            if (count($this->dueBills) === 0) {
                session()->flash('sorgula_success', 'لا يوجد فواتير مستحقة الدفع.');
            } else {
                session()->flash('sorgula_success', 'تم الاستعلام بنجاح. يوجد ' . count($this->dueBills) . ' فواتير مستحقة.');
            }
        } else {
            $this->isQueried = false;
            $errorMsg = $result['Message_TR'] ?? 'فشل الاستعلام. يرجى التحقق من رقم الاشتراك والمحاولة مجدداً.';
            session()->flash('sorgula_error', $errorMsg);
        }
    }

    public function payBill($billIndex)
    {
        \Illuminate\Support\Facades\Log::info("payBill called with index: " . $billIndex);
        
        if (!isset($this->dueBills[$billIndex])) {
            \Illuminate\Support\Facades\Log::error("Bill not found at index: " . $billIndex);
            session()->flash('pay_error', 'الفاتورة غير موجودة.');
            return;
        }

        $bill = $this->dueBills[$billIndex];
        
        // Extract necessary fields based on typical PayStore response
        $amount = (float)($bill['Amount'] ?? $bill['BillAmount'] ?? 0);
        $faturaNo = $bill['BillOrderNumber'] ?? $bill['InvoiceNo'] ?? '';
        $billInquiryId = $bill['BillInquiryId'] ?? null;

        if (!$amount) {
            \Illuminate\Support\Facades\Log::error("Invalid bill amount: " . json_encode($bill));
            session()->flash('pay_error', 'مبلغ الفاتورة غير صالح.');
            return;
        }

        $user = auth()->user();
        $manager = app(BillPaymentManager::class);
        
        \Illuminate\Support\Facades\Log::info("Processing payment for amount: " . $amount);
        $result = $manager->processPayment(
            $user,
            (int)$this->selectedKurumId,
            $this->aboneNo,
            $amount,
            $faturaNo,
            $billInquiryId
        );
        
        \Illuminate\Support\Facades\Log::info("Payment result: " . json_encode($result));

        if ($result['success']) {
            session()->flash('pay_success', $result['message']);
            // Remove paid bill from list
            unset($this->dueBills[$billIndex]);
            $this->dueBills = array_values($this->dueBills); // re-index
            $this->resetPage();
        } else {
            session()->flash('pay_error', $result['message']);
        }
    }

    public function resetForm()
    {
        $this->selectedKurumId = null;
        $this->aboneNo = '';
        $this->queriedName = '';
        $this->isQueried = false;
        $this->dueBills = [];
    }

    public function render()
    {
        return view('livewire.admin.bill-payments-dashboard');
    }
}
