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
    public $existingLogos = [];

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
        
        $this->loadLogos();

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

    public function loadLogos()
    {
        $settings = \App\Models\CompanySetting::all()->keyBy('company_code');
        $this->existingLogos = [];
        foreach ($settings as $code => $setting) {
            $this->existingLogos[$code] = $setting->logo_path;
        }
    }

    protected function categorizeKurumlar(array $kurumlar): array
    {
        $categories = [
            'مدفوعات التلفزيون الخاص (Özel TV Ödemeleri)' => [],
            'مؤسسات إسطنبول (İstanbul Kurumları)'         => [],
            'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)' => [],
            'تحصيل اتصالات GSM (GSM Telekom Tahsilatı)'   => [],
            'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'    => [],
            'تحصيل الكهرباء (Elektrik Tahsilatı)'         => [],
            'تحصيل المياه (Su Tahsilatı)'                 => [],
            'مدفوعات المؤسسات (Kurum Ödemeleri)'          => [],
        ];

        // Hardcoded mapping: CompanyCode => category (or multiple categories via array)
        // Based on the official BayiWebPanel website tabs shown in the provided images
        $codeMap = [
            // ── ÖZEL TV ÖDEMELERİ ──────────────────
            65  => ['مدفوعات التلفزيون الخاص (Özel TV Ödemeleri)'], // D-SMART TV
            8   => ['مدفوعات التلفزيون الخاص (Özel TV Ödemeleri)'], // DİGİTURK
            12  => ['مدفوعات التلفزيون الخاص (Özel TV Ödemeleri)'], // TÜRKSAT - TV

            // ── İSTANBUL KURUMLARI ─────────────────
            116 => ['مؤسسات إسطنبول (İstanbul Kurumları)'], // İSTANBUL AESAŞ (AYEDAŞ ENERJİSA)
            115 => ['مؤسسات إسطنبول (İstanbul Kurumları)'], // İSTANBUL BEDAŞ(CK) = CK BEDAŞ BANKA
            101 => ['مؤسسات إسطنبول (İstanbul Kurumları)'], // ÇORUH EDAŞ AKSA ENERJİ = CK BOĞAZİÇİ
            17  => ['مؤسسات إسطنبول (İstanbul Kurumları)'], // İGDAŞ
            16  => ['مؤسسات إسطنبول (İstanbul Kurumları)'], // İSKİ İSTANBUL SU

            // ── ADSL İNTERNET TAHSİLATI ────────────
            9   => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // D-SMART SMİLE ADSL
            24  => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // MİLLENİCOM DOPİNG
            10  => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // TURKCELL SUPERONLİNE
            38  => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // TURKNET
            93  => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // TÜRKTELEKOM İNTERNET
            407 => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // ATLANTİS NET
            344 => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // GÖKNET
            410 => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // MEKROTİK İNTERNET
            305 => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // ORİS TELEKOM
            23  => ['تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)'], // VODAFONE KOÇNET

            // ── GSM TELEKOM TAHSİLATI ──────────────
            2   => ['تحصيل اتصالات GSM (GSM Telekom Tahsilatı)'], // TURKCELL
            1   => ['تحصيل اتصالات GSM (GSM Telekom Tahsilatı)'], // TÜRK TELEKOM EV-İŞ
            4   => ['تحصيل اتصالات GSM (GSM Telekom Tahsilatı)'], // TÜRKTELEKOM MOBİL
            3   => ['تحصيل اتصالات GSM (GSM Telekom Tahsilatı)'], // VODAFONE
            92  => ['تحصيل اتصالات GSM (GSM Telekom Tahsilatı)'], // TTNET MOBİLE

            // ── DOĞALGAZ TAHSİLATI ────────────────
            53  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // ADAPAZARI GAZ (SAKARYA)
            306 => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // AKSA ADANA GAZ
            315 => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // AKSA MALATYA GAZ
            348 => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // AKSA TOKAT AMASYA GAZ
            160 => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // AKSA VAN GAZ
            405 => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // AKMERCAN SİNOP
            18  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // ANKARA GAZ EGO
            6   => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // BURSA GAZ
            397 => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // ENERYA ANTALYAGAZ
            31  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // ENERYA KAPADOKYA GAZ
            28  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // ENERYA KONYA GAZNET
            50  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // ESKİŞEHİR ESGAZ
            21  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // İZMİR GAZ
            29  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // İZMİT GAZ
            85  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // KAYSERİ DOĞALGAZ
            76  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // KIRGAZ KIRŞEHİR
            27  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // NETGAZ KONYA EREĞLİ
            75  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // SAMGAZ SAMSUN
            51  => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // SÜRMELİ GAZ (YOZGAT)
            120 => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // TOROSGAZ GAZ
            394 => ['تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)'], // TRAKYA GAZ

            // ── ELEKTİRİK TAHSİLATI ───────────────
            328 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // AKDENİZ ELEKTRİK
            20  => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // AYDEM ELEKTRİK
            67  => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // BAŞKENT(ANKARA ENERJİSA)
            129 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // ÇAMLIBEL ELEKTRİK
            334 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // DİCLE EDAŞ
            341 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // ESKİŞEHİR OSMANGAZİ OEDAŞ(ZORLU)
            124 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // FIRAT EDAŞ
            314 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // GEDİZ ELEKTRİK
            87  => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // KAYSERİ ELEKTRİK
            303 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // MERAM (MEPAŞ)
            70  => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // SEPAŞ ENERJİ
            318 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // TOROSLAR ELEKTRİK
            138 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // TREDAŞ ELEKTRİK
            122 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // ULUDAĞ ELEKTRİK
            342 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // VANGÖLÜ EDAŞ
            99  => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // YEŞİLIRMAK YEPAŞ
            333 => ['تحصيل الكهرباء (Elektrik Tahsilatı)'], // ZORLU ELEKTİRİK

            // ── SU TAHSİLATI ──────────────────────
            36  => ['تحصيل المياه (Su Tahsilatı)'], // ADAPAZARI(SAKARYA) SU
            14  => ['تحصيل المياه (Su Tahsilatı)'], // ANTALYA SU ASAT
            47  => ['تحصيل المياه (Su Tahsilatı)'], // ASKİ ANKARA SU
            7   => ['تحصيل المياه (Su Tahsilatı)'], // BURSA SU
            48  => ['تحصيل المياه (Su Tahsilatı)'], // ESKİŞEHİR SU ESKİ
            44  => ['تحصيل المياه (Su Tahsilatı)'], // GASKİ GAZİANTEP SU
            337 => ['تحصيل المياه (Su Tahsilatı)'], // HATAY SU
            139 => ['تحصيل المياه (Su Tahsilatı)'], // İZMİR SU
            30  => ['تحصيل المياه (Su Tahsilatı)'], // İZMİT SU
            86  => ['تحصيل المياه (Su Tahsilatı)'], // KASKİ KAYSERİ SU
            409 => ['تحصيل المياه (Su Tahsilatı)'], // KIRŞEHİR SU
            13  => ['تحصيل المياه (Su Tahsilatı)'], // KONYA SU
            109 => ['تحصيل المياه (Su Tahsilatı)'], // MALATYA SU
            72  => ['تحصيل المياه (Su Tahsilatı)'], // MASKİ MANİSA SU
            15  => ['تحصيل المياه (Su Tahsilatı)'], // NEVŞEHİR SU
            45  => ['تحصيل المياه (Su Tahsilatı)'], // SAMSUN SU
            310 => ['تحصيل المياه (Su Tahsilatı)'], // SASKİ SAKARYA SU
            123 => ['تحصيل المياه (Su Tahsilatı)'], // ŞANLIURFA SU
            134 => ['تحصيل المياه (Su Tahsilatı)'], // TOKAT SU
            103 => ['تحصيل المياه (Su Tahsilatı)'], // VAN SU
            336 => ['تحصيل المياه (Su Tahsilatı)'], // YOZGAT SU

            // ── KURUM ÖDEMELERİ ──────────────────
            330 => ['مدفوعات المؤسسات (Kurum Ödemeleri)'], // HGS YÜKLEME(PLAKA)
            91  => ['مدفوعات المؤسسات (Kurum Ödemeleri)'], // MOTORLU TAŞITLAR VERGİSİ
            89  => ['مدفوعات المؤسسات (Kurum Ödemeleri)'], // SSK CARİ DÖNEM
            369 => ['مدفوعات المؤسسات (Kurum Ödemeleri)'], // SSK GEÇMİŞ DÖNEM
        ];
        
        $companyNamesAr = [
            // ── ÖZEL TV ÖDEMELERİ ──────────────────
            65  => 'دي سمارت (D-SMART TV)',
            8   => 'ديجي تورك (DİGİTURK)',
            12  => 'تورك سات - تلفزيون (TÜRKSAT - TV)',

            // ── İSTANBUL KURUMLARI ─────────────────
            116 => 'أيداش إسطنبول (İSTANBUL AESAŞ)',
            115 => 'بيداش إسطنبول (CK BEDAŞ)',
            101 => 'كوروه إيداش (CK BOĞAZİÇİ)',
            17  => 'إيغداش للغاز (İGDAŞ)',
            16  => 'إيسكي للمياه (İSKİ İSTANBUL SU)',

            // ── ADSL İNTERNET TAHSİLATI ────────────
            9   => 'دي سمارت سمايل (D-SMART SMİLE ADSL)',
            24  => 'ميلينيكوم دوبينج (MİLLENİCOM DOPİNG)',
            10  => 'توركسيل سوبر أونلاين (TURKCELL SUPERONLİNE)',
            38  => 'تورك نت (TURKNET)',
            93  => 'تورك تيليكوم للإنترنت (TÜRKTELEKOM İNTERNET)',
            407 => 'أتلانتيس نت (ATLANTİS NET)',
            344 => 'جوك نت (GÖKNET)',
            410 => 'ميكروتيك إنترنت (MEKROTİK İNTERNET)',
            305 => 'أوريس تيليكوم (ORİS TELEKOM)',
            23  => 'فودافون كوتش نت (VODAFONE KOÇNET)',

            // ── GSM TELEKOM TAHSİLATI ──────────────
            2   => 'توركسيل (TURKCELL)',
            1   => 'تورك تيليكوم (TÜRK TELEKOM EV-İŞ)',
            4   => 'تورك تيليكوم موبايل (TÜRKTELEKOM MOBİL)',
            3   => 'فودافون (VODAFONE)',
            92  => 'تي تي نت موبايل (TTNET MOBİLE)',

            // ── DOĞALGAZ TAHSİLATI ────────────────
            53  => 'غاز أدا بازاري (ADAPAZARI GAZ)',
            306 => 'غاز أكسا أضنة (AKSA ADANA GAZ)',
            315 => 'غاز أكسا ملاطية (AKSA MALATYA GAZ)',
            348 => 'غاز أكسا توكات (AKSA TOKAT AMASYA GAZ)',
            160 => 'غاز أكسا وان (AKSA VAN GAZ)',
            405 => 'أكمرجان سينوب (AKMERCAN SİNOP)',
            18  => 'غاز أنقرة (ANKARA GAZ EGO)',
            6   => 'غاز بورصة (BURSA GAZ)',
            397 => 'غاز إنريا أنطاليا (ENERYA ANTALYAGAZ)',
            31  => 'غاز إنريا كابادوكيا (ENERYA KAPADOKYA GAZ)',
            28  => 'غاز إنريا قونيا (ENERYA KONYA GAZNET)',
            50  => 'غاز إسكي شهير (ESKİŞEHİR ESGAZ)',
            21  => 'غاز إزمير (İZMİR GAZ)',
            29  => 'غاز إزميت (İZMİT GAZ)',
            85  => 'غاز قيصري (KAYSERİ DOĞALGAZ)',
            76  => 'غاز كيرشهير (KIRGAZ KIRŞEHİR)',
            27  => 'غاز نت قونيا (NETGAZ KONYA)',
            75  => 'غاز سامسون (SAMGAZ SAMSUN)',
            51  => 'غاز سورميلي (SÜRMELİ GAZ)',
            120 => 'غاز طوروس (TOROSGAZ GAZ)',
            394 => 'غاز تراكيا (TRAKYA GAZ)',

            // ── ELEKTİRİK TAHSİLATI ───────────────
            328 => 'كهرباء البحر الأبيض المتوسط (AKDENİZ ELEKTRİK)',
            20  => 'كهرباء آيديم (AYDEM ELEKTRİK)',
            67  => 'كهرباء العاصمة (BAŞKENT ENERJİSA)',
            129 => 'كهرباء تشاملي بيل (ÇAMLIBEL ELEKTRİK)',
            334 => 'كهرباء دجلة (DİCLE EDAŞ)',
            341 => 'كهرباء عثمان غازي (ESKİŞEHİR OSMANGAZİ)',
            124 => 'كهرباء الفرات (FIRAT EDAŞ)',
            314 => 'كهرباء غيديز (GEDİZ ELEKTRİK)',
            87  => 'كهرباء قيصري (KAYSERİ ELEKTRİK)',
            303 => 'كهرباء ميرام (MERAM MEPAŞ)',
            70  => 'سيباش للطاقة (SEPAŞ ENERJİ)',
            318 => 'كهرباء طوروسلار (TOROSLAR ELEKTRİK)',
            138 => 'كهرباء تريداش (TREDAŞ ELEKTRİK)',
            122 => 'كهرباء أولوداغ (ULUDAĞ ELEKTRİK)',
            342 => 'كهرباء وان جوليو (VANGÖLÜ EDAŞ)',
            99  => 'كهرباء يشيل إرماك (YEŞİLIRMAK YEPAŞ)',
            333 => 'كهرباء زورلو (ZORLU ELEKTİRİK)',

            // ── SU TAHSİLATI ──────────────────────
            36  => 'مياه أدا بازاري (ADAPAZARI SU)',
            14  => 'مياه أنطاليا (ANTALYA SU ASAT)',
            47  => 'مياه أنقرة (ASKİ ANKARA SU)',
            7   => 'مياه بورصة (BURSA SU)',
            48  => 'مياه إسكي شهير (ESKİŞEHİR SU ESKİ)',
            44  => 'مياه غازي عنتاب (GASKİ GAZİANTEP SU)',
            337 => 'مياه هاتاي (HATAY SU)',
            139 => 'مياه إزمير (İZMİR SU)',
            30  => 'مياه إزميت (İZMİT SU)',
            86  => 'مياه قيصري (KASKİ KAYSERİ SU)',
            409 => 'مياه كيرشهير (KIRŞEHİR SU)',
            13  => 'مياه قونيا (KONYA SU)',
            109 => 'مياه ملاطية (MALATYA SU)',
            72  => 'مياه مانيسا (MASKİ MANİSA SU)',
            15  => 'مياه نوشهير (NEVŞEHİR SU)',
            45  => 'مياه سامسون (SAMSUN SU)',
            310 => 'مياه سكاريا (SASKİ SAKARYA SU)',
            123 => 'مياه شانلي أورفا (ŞANLIURFA SU)',
            134 => 'مياه توكات (TOKAT SU)',
            103 => 'مياه وان (VAN SU)',
            336 => 'مياه يوزغات (YOZGAT SU)',

            // ── KURUM ÖDEMELERİ ──────────────────
            330 => 'تعبئة HGS (اللوحة) (HGS YÜKLEME)',
            91  => 'ضريبة السيارات (MOTORLU TAŞITLAR VERGİSİ)',
            89  => 'تأمينات اجتماعية - حالي (SSK CARİ DÖNEM)',
            369 => 'تأمينات اجتماعية - سابق (SSK GEÇMİŞ DÖNEM)',
        ];

        foreach ($kurumlar as $kurum) {
            $rawName = $kurum['Name'] ?? $kurum['adi'] ?? '';
            $code    = (int)($kurum['CompanyCode'] ?? $kurum['id'] ?? 0);
            if (!$code) continue;

            $mappedName = $companyNamesAr[$code] ?? $rawName;
            $mappedKurum = ['id' => $code, 'name' => $mappedName];

            if (isset($codeMap[$code])) {
                foreach ($codeMap[$code] as $cat) {
                    $categories[$cat][] = $mappedKurum;
                }
            } else {
                // Fallback: uncategorised goes to KURUM ÖDEMELERİ
                $categories['مدفوعات المؤسسات'][] = $mappedKurum;
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
