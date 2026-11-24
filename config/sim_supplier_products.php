<?php

/*
 | Data packages each SIM supplier lists in its panel («Kontor Fiyat Ayarları» → Callback), used by
 | «إدارة الباقات والأسعار» to link a package by picking the product instead of typing its key.
 | Key sent to the API = operator + tip + code ("Callback (TeleCard) …" → Callback / TeleCard / code).
 | Cost = the supplier's price to us (ALIŞ) when the list was read; the real cost of every load comes
 | from the supplier's reply. The suppliers have no product-list API: update this file from a new
 | screenshot when they add, remove or reprice products.
 |   kontor86: list of 2026-11-18 · telekomtl: list of 2026-11-21 (TL credit and minute products left out).
 */

$p = fn (string $code, string $tip, string $name, float $cost, string $group) => compact('code', 'tip', 'name', 'cost', 'group') + ['operator' => 'Callback'];

return [
    'kontor86' => [
        $p('26240', 'TeleCard', 'FİZİKSEL Turkiye 5 GB · 30 gün', 215, 'Tele CARD'),
        $p('26241', 'TeleCard', 'FİZİKSEL Turkiye 10 GB · 30 gün', 230, 'Tele CARD'),
        $p('26250', 'TeleCard', 'FİZİKSEL Turkiye 20 GB · 30 gün', 350, 'Tele CARD'),
        $p('26255', 'TeleCard', 'FİZİKSEL Turkiye 25 GB · 30 gün', 540, 'Tele CARD'),
        $p('26257', 'TeleCard', 'FİZİKSEL Turkiye 30 GB · 30 gün', 580, 'Tele CARD'),
        $p('26260', 'TeleCard', 'FİZİKSEL Turkiye 50 GB · 30 gün', 690, 'Tele CARD'),
        $p('26265', 'TeleCard', 'FİZİKSEL Turkiye 100 GB · 30 gün', 1350, 'Tele CARD'),
        $p('26270', 'TeleCard', 'FİZİKSEL Turkiye 50 GB · 180 gün', 870, 'Tele CARD'),
        $p('35230', 'TeleCard', 'E-SİM Turkiye 3 GB · 30 gün', 165, 'Tele CARD eSIM'),
        $p('35240', 'TeleCard', 'E-SİM Turkiye 5 GB · 30 gün', 215, 'Tele CARD eSIM'),
        $p('35250', 'TeleCard', 'E-SİM Turkiye 10 GB · 30 gün', 255, 'Tele CARD eSIM'),
        $p('35270', 'TeleCard', 'E-SİM Turkiye 20 GB · 30 gün', 375, 'Tele CARD eSIM'),
        $p('35280', 'TeleCard', 'E-SİM Turkiye 30 GB · 30 gün', 580, 'Tele CARD eSIM'),
        $p('35290', 'TeleCard', 'E-SİM Turkiye 50 GB · 30 gün', 715, 'Tele CARD eSIM'),
        $p('35300', 'TeleCard', 'E-SİM Turkiye 100 GB · 30 gün', 1350, 'Tele CARD eSIM'),
        $p('35310', 'TeleCard', 'E-SİM Turkiye 50 GB · 180 gün', 975, 'Tele CARD eSIM'),
        $p('45240', 'eSIM', 'AVRUPA FİZİKSEL 5 GB · 30 gün', 260, 'Avrupa'),
        $p('45241', 'eSIM', 'AVRUPA FİZİKSEL 10 GB · 30 gün', 350, 'Avrupa'),
        $p('45242', 'eSIM', 'AVRUPA FİZİKSEL 20 GB · 30 gün', 470, 'Avrupa'),
        $p('45243', 'eSIM', 'AVRUPA FİZİKSEL 25 GB · 30 gün', 680, 'Avrupa'),
        $p('45244', 'eSIM', 'AVRUPA FİZİKSEL 50 GB · 30 gün', 995, 'Avrupa'),
        $p('45254', 'eSIM', 'AVRUPA ESİM 5 GB · 30 gün', 260, 'Avrupa eSIM'),
        $p('45255', 'eSIM', 'AVRUPA ESİM 10 GB · 30 gün', 320, 'Avrupa eSIM'),
        $p('45256', 'eSIM', 'AVRUPA ESİM 20 GB · 30 gün', 470, 'Avrupa eSIM'),
        $p('45257', 'eSIM', 'AVRUPA ESİM 25 GB · 30 gün', 680, 'Avrupa eSIM'),
        $p('45258', 'eSIM', 'AVRUPA ESİM 50 GB · 30 gün', 1300, 'Avrupa eSIM'),
    ],

    'telekomtl' => [
        $p('1233', 'TeleCard', 'Avrupa 5 GB', 220, 'Avrupa'),
        $p('1234', 'TeleCard', 'Avrupa 10 GB', 295, 'Avrupa'),
        $p('12346', 'TeleCard', 'Avrupa 20 GB', 460, 'Avrupa'),
        $p('12347', 'TeleCard', 'Avrupa 50 GB', 975, 'Avrupa'),
        $p('185', 'eSIM', 'TURKTELEKOM 2 GB · 30 gün', 68, 'Türk Telekom eSIM'),
        $p('115', 'eSIM', 'TURKTELEKOM 5 GB · 30 gün', 132, 'Türk Telekom eSIM'),
        $p('175', 'eSIM', 'TURKTELEKOM 10 GB · 30 gün', 199, 'Türk Telekom eSIM'),
        $p('225', 'eSIM', 'TURKTELEKOM 15 GB · 30 gün', 270, 'Türk Telekom eSIM'),
        $p('330', 'eSIM', 'TURKTELEKOM 20 GB · 30 gün', 349, 'Türk Telekom eSIM'),
        $p('440', 'eSIM', 'TURKTELEKOM 25 GB · 30 gün', 461, 'Türk Telekom eSIM'),
        $p('480', 'eSIM', 'TURKTELEKOM 30 GB · 30 gün', 500, 'Türk Telekom eSIM'),
        $p('590', 'eSIM', 'TURKTELEKOM 50 GB · 30 gün', 615, 'Türk Telekom eSIM'),
        $p('1600', 'eSIM', 'TURKTELEKOM 100 GB · 30 gün', 1299, 'Türk Telekom eSIM'),
        $p('2000', 'eSIM', 'TURKTELEKOM 50 GB · 180 gün', 799, 'Türk Telekom eSIM'),
        $p('909090', 'eSIM', 'TURKTELEKOM 100 GB · 90 gün', 1650, 'Türk Telekom eSIM'),
        $p('95', 'eSIM', 'Turkcell 2 GB · 30 gün', 125, 'Turkcell eSIM'),
        $p('99', 'eSIM', 'Turkcell 5 GB · 30 gün', 248, 'Turkcell eSIM'),
        $p('100.01', 'eSIM', 'Turkcell 10 GB · 30 gün', 360, 'Turkcell eSIM'),
        $p('100.03', 'eSIM', 'Turkcell 20 GB · 30 gün', 690, 'Turkcell eSIM'),
        $p('700.01', 'eSIM', 'Turkcell 20 GB · 90 gün', 730, 'Turkcell eSIM'),
        $p('1670', 'eSIM', 'Turkcell 50 GB · 30 gün', 1710, 'Turkcell eSIM'),
        $p('200.01', 'eSIM', 'Vodafone 10 GB · 30 gün', 235, 'Vodafone eSIM'),
        $p('200.04', 'eSIM', 'Vodafone 20 GB · 30 gün', 399, 'Vodafone eSIM'),
        $p('1', 'OnlineKart', 'OnlineKart TT 500 MB · 7 gün', 35, 'OnlineKart (Türk Telekom)'),
        $p('2', 'OnlineKart', 'OnlineKart TT 1 GB · 1 gün', 35, 'OnlineKart (Türk Telekom)'),
        $p('777', 'OnlineKart', 'OnlineKart TT 5 GB · 30 gün', 140, 'OnlineKart (Türk Telekom)'),
        $p('778', 'OnlineKart', 'OnlineKart TT 7 GB · 30 gün', 160, 'OnlineKart (Türk Telekom)'),
        $p('1111', 'OnlineKart', 'OnlineKart TT 10 GB · 30 gün', 175, 'OnlineKart (Türk Telekom)'),
        $p('2222', 'OnlineKart', 'OnlineKart TT 20 GB · 30 gün', 355, 'OnlineKart (Türk Telekom)'),
        $p('2555', 'OnlineKart', 'OnlineKart TT 25 GB · 30 gün', 420, 'OnlineKart (Türk Telekom)'),
        $p('5555', 'OnlineKart', 'OnlineKart TT 50 GB · 30 gün', 620, 'OnlineKart (Türk Telekom)'),
        $p('100100', 'OnlineKart', 'OnlineKart TT 100 GB · 30 gün', 1320, 'OnlineKart (Türk Telekom)'),
        $p('180180', 'OnlineKart', 'OnlineKart TT 49 GB · 180 gün', 900, 'OnlineKart (Türk Telekom)'),
        $p('3', 'BimCell', 'BimCell Dost Küçük 3 GB + 750 DK', 290, 'BimCell'),
        $p('9', 'BimCell', 'BimCell Dost Büyük 9 GB + 750 DK', 340, 'BimCell'),
        $p('12', 'BimCell', 'BimCell Dost Knsn 12 GB + 1500 DK', 425, 'BimCell'),
        $p('11', 'BimCell', 'BimCell Dost Ultra 20 GB + 750 DK', 445, 'BimCell'),
        $p('501', 'PttCell', 'PttCell SADE 5 GB', 100, 'PttCell'),
        $p('502', 'PttCell', 'PttCell SADE 10 GB', 126, 'PttCell'),
        $p('503', 'PttCell', 'PttCell SADE 20 GB', 172, 'PttCell'),
        $p('504', 'PttCell', 'PttCell SADE 30 GB', 199, 'PttCell'),
    ],
];
