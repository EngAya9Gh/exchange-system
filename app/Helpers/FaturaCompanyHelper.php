<?php

namespace App\Helpers;

class FaturaCompanyHelper
{
    public static function getDetails($kurumId)
    {
        $code = (int)$kurumId;

        $codeMap = [
            // ── ÖZEL TV ÖDEMELERİ ──────────────────
            65  => 'مدفوعات التلفزيون الخاص (Özel TV Ödemeleri)', // D-SMART TV
            8   => 'مدفوعات التلفزيون الخاص (Özel TV Ödemeleri)', // DİGİTURK
            12  => 'مدفوعات التلفزيون الخاص (Özel TV Ödemeleri)', // TÜRKSAT - TV

            // ── İSTANBUL KURUMLARI ─────────────────
            116 => 'مؤسسات إسطنبول (İstanbul Kurumları)', // İSTANBUL AESAŞ (AYEDAŞ ENERJİSA)
            115 => 'مؤسسات إسطنبول (İstanbul Kurumları)', // İSTANBUL BEDAŞ(CK) = CK BEDAŞ BANKA
            101 => 'مؤسسات إسطنبول (İstanbul Kurumları)', // ÇORUH EDAŞ AKSA ENERJİ = CK BOĞAZİÇİ
            17  => 'مؤسسات إسطنبول (İstanbul Kurumları)', // İGDAŞ
            16  => 'مؤسسات إسطنبول (İstanbul Kurumları)', // İSKİ İSTANBUL SU

            // ── ADSL İNTERNET TAHSİLATI ────────────
            9   => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // D-SMART SMİLE ADSL
            24  => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // MİLLENİCOM DOPİNG
            10  => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // TURKCELL SUPERONLİNE
            38  => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // TURKNET
            93  => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // TÜRKTELEKOM İNTERNET
            407 => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // ATLANTİS NET
            344 => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // GÖKNET
            410 => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // MEKROTİK İNTERNET
            305 => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // ORİS TELEKOM
            23  => 'تحصيل إنترنت ADSL (ADSL İnternet Tahsilatı)', // VODAFONE KOÇNET

            // ── GSM TELEKOM TAHSİLATI ──────────────
            2   => 'تحصيل اتصالات GSM (GSM Telekom Tahsilatı)', // TURKCELL
            1   => 'تحصيل اتصالات GSM (GSM Telekom Tahsilatı)', // TÜRK TELEKOM EV-İŞ
            4   => 'تحصيل اتصالات GSM (GSM Telekom Tahsilatı)', // TÜRKTELEKOM MOBİL
            3   => 'تحصيل اتصالات GSM (GSM Telekom Tahsilatı)', // VODAFONE
            92  => 'تحصيل اتصالات GSM (GSM Telekom Tahsilatı)', // TTNET MOBİLE

            // ── DOĞALGAZ TAHSİLATI ────────────────
            53  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // ADAPAZARI GAZ (SAKARYA)
            306 => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // AKSA ADANA GAZ
            315 => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // AKSA MALATYA GAZ
            348 => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // AKSA TOKAT AMASYA GAZ
            160 => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // AKSA VAN GAZ
            405 => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // AKMERCAN SİNOP
            18  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // ANKARA GAZ EGO
            6   => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // BURSA GAZ
            397 => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // ENERYA ANTALYAGAZ
            31  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // ENERYA KAPADOKYA GAZ
            28  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // ENERYA KONYA GAZNET
            50  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // ESKİŞEHİR ESGAZ
            21  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // İZMİR GAZ
            29  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // İZMİT GAZ
            85  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // KAYSERİ DOĞALGAZ
            76  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // KIRGAZ KIRŞEHİR
            27  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // NETGAZ KONYA EREĞLİ
            75  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // SAMGAZ SAMSUN
            51  => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // SÜRMELİ GAZ (YOZGAT)
            120 => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // TOROSGAZ GAZ
            394 => 'تحصيل الغاز الطبيعي (Doğalgaz Tahsilatı)', // TRAKYA GAZ

            // ── ELEKTİRİK TAHSİLATI ───────────────
            328 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // AKDENİZ ELEKTRİK
            20  => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // AYDEM ELEKTRİK
            67  => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // BAŞKENT(ANKARA ENERJİSA)
            129 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // ÇAMLIBEL ELEKTRİK
            334 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // DİCLE EDAŞ
            341 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // ESKİŞEHİR OSMANGAZİ OEDAŞ(ZORLU)
            124 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // FIRAT EDAŞ
            314 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // GEDİZ ELEKTRİK
            87  => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // KAYSERİ ELEKTRİK
            303 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // MERAM (MEPAŞ)
            70  => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // SEPAŞ ENERJİ
            318 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // TOROSLAR ELEKTRİK
            138 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // TREDAŞ ELEKTRİK
            122 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // ULUDAĞ ELEKTRİK
            342 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // VANGÖLÜ EDAŞ
            99  => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // YEŞİLIRMAK YEPAŞ
            333 => 'تحصيل الكهرباء (Elektrik Tahsilatı)', // ZORLU ELEKTİRİK

            // ── SU TAHSİLATI ──────────────────────
            36  => 'تحصيل المياه (Su Tahsilatı)', // ADAPAZARI(SAKARYA) SU
            14  => 'تحصيل المياه (Su Tahsilatı)', // ANTALYA SU ASAT
            47  => 'تحصيل المياه (Su Tahsilatı)', // ASKİ ANKARA SU
            7   => 'تحصيل المياه (Su Tahsilatı)', // BURSA SU
            48  => 'تحصيل المياه (Su Tahsilatı)', // ESKİŞEHİR SU ESKİ
            44  => 'تحصيل المياه (Su Tahsilatı)', // GASKİ GAZİANTEP SU
            337 => 'تحصيل المياه (Su Tahsilatı)', // HATAY SU
            139 => 'تحصيل المياه (Su Tahsilatı)', // İZMİR SU
            30  => 'تحصيل المياه (Su Tahsilatı)', // İZMİT SU
            86  => 'تحصيل المياه (Su Tahsilatı)', // KASKİ KAYSERİ SU
            409 => 'تحصيل المياه (Su Tahsilatı)', // KIRŞEHİR SU
            13  => 'تحصيل المياه (Su Tahsilatı)', // KONYA SU
            109 => 'تحصيل المياه (Su Tahsilatı)', // MALATYA SU
            72  => 'تحصيل المياه (Su Tahsilatı)', // MASKİ MANİSA SU
            15  => 'تحصيل المياه (Su Tahsilatı)', // NEVŞEHİR SU
            45  => 'تحصيل المياه (Su Tahsilatı)', // SAMSUN SU
            310 => 'تحصيل المياه (Su Tahsilatı)', // SASKİ SAKARYA SU
            123 => 'تحصيل المياه (Su Tahsilatı)', // ŞANLIURFA SU
            134 => 'تحصيل المياه (Su Tahsilatı)', // TOKAT SU
            103 => 'تحصيل المياه (Su Tahsilatı)', // VAN SU
            336 => 'تحصيل المياه (Su Tahsilatı)', // YOZGAT SU

            // ── KURUM ÖDEMELERİ ──────────────────
            330 => 'مدفوعات المؤسسات (Kurum Ödemeleri)', // HGS YÜKLEME(PLAKA)
            91  => 'مدفوعات المؤسسات (Kurum Ödemeleri)', // MOTORLU TAŞITLAR VERGİSİ
            89  => 'مدفوعات المؤسسات (Kurum Ödemeleri)', // SSK CARİ DÖNEM
            369 => 'مدفوعات المؤسسات (Kurum Ödemeleri)', // SSK GEÇMİŞ DÖNEM
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

        return [
            'name' => $companyNamesAr[$code] ?? 'غير معروف',
            'category' => $codeMap[$code] ?? 'مدفوعات المؤسسات (Kurum Ödemeleri)'
        ];
    }
}
