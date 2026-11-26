<?php

/*
 | Data packages each SIM supplier lists in its panel («Kontor Fiyat Ayarları» → Callback), used by
 | «إدارة الباقات والأسعار» to link a package by picking the product instead of typing its key.
 | Key sent to the API = operator + tip + code ("Callback (TeleCard) …" → Callback / TeleCard / code).
 | Cost = the supplier's price to us (ALIŞ) when the list was read; the real cost of every load comes
 | from the supplier's reply. The suppliers have no product-list API: update this file from a new
 | screenshot when they add, remove or reprice products.
 |   kontor86: list of 2026-11-25 · telekomtl: list of 2026-11-21 (TL credit and minute products left out).
 */

/*
 | Matching specs (used to link a package to the one product with the same specs, never by price):
 |   region local|europe · sim physical|esim · network as on our cards · gb · days (null = not stated in the list).
 | In our system a physical Turkey SIM always means a Tele CARD line (fixed "50" prefix).
 | fast = kontor86 «HIZLI FİZİKSEL» products: linked only to packages marked «⚡ سريع · Hızlı», never to the others.
 */
$p = function (string $code, string $tip, string $name, float $cost, string $group, string $region, string $sim, string $network, ?float $gb, ?int $days) {
    return compact('code', 'tip', 'name', 'cost', 'group', 'region', 'sim', 'network', 'gb', 'days') + ['operator' => 'Callback'];
};

$list = [
    'kontor86' => [
        $p('26240', 'TeleCard', 'FİZİKSEL Turkiye 5 GB · 30 gün', 171, 'Tele CARD', 'local', 'physical', 'Tele CARD', 5, 30),
        $p('26241', 'TeleCard', 'FİZİKSEL Turkiye 10 GB · 30 gün', 205, 'Tele CARD', 'local', 'physical', 'Tele CARD', 10, 30),
        $p('26250', 'TeleCard', 'FİZİKSEL Turkiye 20 GB · 30 gün', 325, 'Tele CARD', 'local', 'physical', 'Tele CARD', 20, 30),
        $p('26255', 'TeleCard', 'FİZİKSEL Turkiye 25 GB · 30 gün', 515, 'Tele CARD', 'local', 'physical', 'Tele CARD', 25, 30),
        $p('26257', 'TeleCard', 'FİZİKSEL Turkiye 30 GB · 30 gün', 555, 'Tele CARD', 'local', 'physical', 'Tele CARD', 30, 30),
        $p('26260', 'TeleCard', 'FİZİKSEL Turkiye 50 GB · 30 gün', 665, 'Tele CARD', 'local', 'physical', 'Tele CARD', 50, 30),
        $p('26265', 'TeleCard', 'FİZİKSEL Turkiye 100 GB · 30 gün', 1325, 'Tele CARD', 'local', 'physical', 'Tele CARD', 100, 30),
        $p('26270', 'TeleCard', 'FİZİKSEL Turkiye 50 GB · 180 gün', 845, 'Tele CARD', 'local', 'physical', 'Tele CARD', 50, 180),
        $p('35230', 'TeleCard', 'E-SİM Turkiye 3 GB · 30 gün', 140, 'Tele CARD eSIM', 'local', 'esim', 'Tele CARD', 3, 30),
        $p('35240', 'TeleCard', 'E-SİM Turkiye 5 GB · 30 gün', 150, 'Tele CARD eSIM', 'local', 'esim', 'Tele CARD', 5, 30),
        $p('35250', 'TeleCard', 'E-SİM Turkiye 10 GB · 30 gün', 205, 'Tele CARD eSIM', 'local', 'esim', 'Tele CARD', 10, 30),
        $p('35270', 'TeleCard', 'E-SİM Turkiye 20 GB · 30 gün', 325, 'Tele CARD eSIM', 'local', 'esim', 'Tele CARD', 20, 30),
        $p('35280', 'TeleCard', 'E-SİM Turkiye 30 GB · 30 gün', 555, 'Tele CARD eSIM', 'local', 'esim', 'Tele CARD', 30, 30),
        $p('35290', 'TeleCard', 'E-SİM Turkiye 50 GB · 30 gün', 665, 'Tele CARD eSIM', 'local', 'esim', 'Tele CARD', 50, 30),
        $p('35300', 'TeleCard', 'E-SİM Turkiye 100 GB · 30 gün', 1325, 'Tele CARD eSIM', 'local', 'esim', 'Tele CARD', 100, 30),
        $p('35310', 'TeleCard', 'E-SİM Turkiye 50 GB · 180 gün', 950, 'Tele CARD eSIM', 'local', 'esim', 'Tele CARD', 50, 180),
        $p('7036449', 'TeleCard', 'HIZLI FİZİKSEL 5 GB · 30 gün', 140, 'Tele CARD HIZLI (سريع)', 'local', 'physical', 'Tele CARD', 5, 30) + ['fast' => true],
        $p('6987918', 'TeleCard', 'HIZLI FİZİKSEL 10 GB · 30 gün', 190, 'Tele CARD HIZLI (سريع)', 'local', 'physical', 'Tele CARD', 10, 30) + ['fast' => true],
        $p('2652690', 'TeleCard', 'HIZLI FİZİKSEL 20 GB · 30 gün', 290, 'Tele CARD HIZLI (سريع)', 'local', 'physical', 'Tele CARD', 20, 30) + ['fast' => true],
        $p('2285493', 'TeleCard', 'HIZLI FİZİKSEL 50 GB · 30 gün', 555, 'Tele CARD HIZLI (سريع)', 'local', 'physical', 'Tele CARD', 50, 30) + ['fast' => true],
        $p('2205285', 'TeleCard', 'HIZLI FİZİKSEL 100 GB · 30 gün', 1030, 'Tele CARD HIZLI (سريع)', 'local', 'physical', 'Tele CARD', 100, 30) + ['fast' => true],
        $p('45240', 'eSIM', 'AVRUPA FİZİKSEL 5 GB · 30 gün', 260, 'Avrupa', 'europe', 'physical', 'Avrupa', 5, 30),
        $p('45241', 'eSIM', 'AVRUPA FİZİKSEL 10 GB · 30 gün', 350, 'Avrupa', 'europe', 'physical', 'Avrupa', 10, 30),
        $p('45242', 'eSIM', 'AVRUPA FİZİKSEL 20 GB · 30 gün', 470, 'Avrupa', 'europe', 'physical', 'Avrupa', 20, 30),
        $p('45243', 'eSIM', 'AVRUPA FİZİKSEL 25 GB · 30 gün', 680, 'Avrupa', 'europe', 'physical', 'Avrupa', 25, 30),
        $p('45244', 'eSIM', 'AVRUPA FİZİKSEL 50 GB · 30 gün', 995, 'Avrupa', 'europe', 'physical', 'Avrupa', 50, 30),
        $p('45254', 'eSIM', 'AVRUPA ESİM 5 GB · 30 gün', 260, 'Avrupa eSIM', 'europe', 'esim', 'Avrupa', 5, 30),
        $p('45255', 'eSIM', 'AVRUPA ESİM 10 GB · 30 gün', 320, 'Avrupa eSIM', 'europe', 'esim', 'Avrupa', 10, 30),
        $p('45256', 'eSIM', 'AVRUPA ESİM 20 GB · 30 gün', 470, 'Avrupa eSIM', 'europe', 'esim', 'Avrupa', 20, 30),
        $p('45257', 'eSIM', 'AVRUPA ESİM 25 GB · 30 gün', 680, 'Avrupa eSIM', 'europe', 'esim', 'Avrupa', 25, 30),
        $p('45258', 'eSIM', 'AVRUPA ESİM 50 GB · 30 gün', 1300, 'Avrupa eSIM', 'europe', 'esim', 'Avrupa', 50, 30),
    ],

    'telekomtl' => [
        $p('1233', 'TeleCard', 'Avrupa 5 GB', 220, 'Avrupa', 'europe', 'physical', 'Avrupa', 5, 30),
        $p('1234', 'TeleCard', 'Avrupa 10 GB', 295, 'Avrupa', 'europe', 'physical', 'Avrupa', 10, 30),
        $p('12346', 'TeleCard', 'Avrupa 20 GB', 460, 'Avrupa', 'europe', 'physical', 'Avrupa', 20, 30),
        $p('12347', 'TeleCard', 'Avrupa 50 GB', 975, 'Avrupa', 'europe', 'physical', 'Avrupa', 50, 30),
        $p('185', 'eSIM', 'TURKTELEKOM 2 GB · 30 gün', 68, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 2, 30),
        $p('115', 'eSIM', 'TURKTELEKOM 5 GB · 30 gün', 132, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 5, 30),
        $p('175', 'eSIM', 'TURKTELEKOM 10 GB · 30 gün', 199, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 10, 30),
        $p('225', 'eSIM', 'TURKTELEKOM 15 GB · 30 gün', 270, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 15, 30),
        $p('330', 'eSIM', 'TURKTELEKOM 20 GB · 30 gün', 349, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 20, 30),
        $p('440', 'eSIM', 'TURKTELEKOM 25 GB · 30 gün', 461, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 25, 30),
        $p('480', 'eSIM', 'TURKTELEKOM 30 GB · 30 gün', 500, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 30, 30),
        $p('590', 'eSIM', 'TURKTELEKOM 50 GB · 30 gün', 615, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 50, 30),
        $p('1600', 'eSIM', 'TURKTELEKOM 100 GB · 30 gün', 1299, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 100, 30),
        $p('2000', 'eSIM', 'TURKTELEKOM 50 GB · 180 gün', 799, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 50, 180),
        $p('909090', 'eSIM', 'TURKTELEKOM 100 GB · 90 gün', 1650, 'Türk Telekom eSIM', 'local', 'esim', 'Türk Telekom', 100, 90),
        $p('95', 'eSIM', 'Turkcell 2 GB · 30 gün', 125, 'Turkcell eSIM', 'local', 'esim', 'Turkcell', 2, 30),
        $p('99', 'eSIM', 'Turkcell 5 GB · 30 gün', 248, 'Turkcell eSIM', 'local', 'esim', 'Turkcell', 5, 30),
        $p('100.01', 'eSIM', 'Turkcell 10 GB · 30 gün', 360, 'Turkcell eSIM', 'local', 'esim', 'Turkcell', 10, 30),
        $p('100.03', 'eSIM', 'Turkcell 20 GB · 30 gün', 690, 'Turkcell eSIM', 'local', 'esim', 'Turkcell', 20, 30),
        $p('700.01', 'eSIM', 'Turkcell 20 GB · 90 gün', 730, 'Turkcell eSIM', 'local', 'esim', 'Turkcell', 20, 90),
        $p('1670', 'eSIM', 'Turkcell 50 GB · 30 gün', 1710, 'Turkcell eSIM', 'local', 'esim', 'Turkcell', 50, 30),
        $p('200.01', 'eSIM', 'Vodafone 10 GB · 30 gün', 235, 'Vodafone eSIM', 'local', 'esim', 'Vodafone', 10, 30),
        $p('200.04', 'eSIM', 'Vodafone 20 GB · 30 gün', 399, 'Vodafone eSIM', 'local', 'esim', 'Vodafone', 20, 30),
        $p('1', 'OnlineKart', 'OnlineKart eSIM TT 500 MB · 7 gün', 35, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 0.5, 7),
        $p('2', 'OnlineKart', 'OnlineKart eSIM TT 1 GB · 1 gün', 35, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 1, 1),
        $p('777', 'OnlineKart', 'OnlineKart eSIM TT 5 GB · 30 gün', 140, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 5, 30),
        $p('778', 'OnlineKart', 'OnlineKart eSIM TT 7 GB · 30 gün', 160, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 7, 30),
        $p('1111', 'OnlineKart', 'OnlineKart eSIM TT 10 GB · 30 gün', 175, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 10, 30),
        $p('2222', 'OnlineKart', 'OnlineKart eSIM TT 20 GB · 30 gün', 355, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 20, 30),
        $p('2555', 'OnlineKart', 'OnlineKart eSIM TT 25 GB · 30 gün', 420, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 25, 30),
        $p('5555', 'OnlineKart', 'OnlineKart eSIM TT 50 GB · 30 gün', 620, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 50, 30),
        $p('100100', 'OnlineKart', 'OnlineKart eSIM TT 100 GB · 30 gün', 1320, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 100, 30),
        $p('180180', 'OnlineKart', 'OnlineKart eSIM TT 49 GB · 180 gün', 900, 'OnlineKart eSIM (Türk Telekom)', 'local', 'esim', 'OnlineKart', 49, 180),
        $p('3', 'BimCell', 'BimCell Dost Küçük 3 GB + 750 DK', 290, 'BimCell', 'local', 'physical', 'BimCell', 3, null),
        $p('9', 'BimCell', 'BimCell Dost Büyük 9 GB + 750 DK', 340, 'BimCell', 'local', 'physical', 'BimCell', 9, null),
        $p('12', 'BimCell', 'BimCell Dost Knsn 12 GB + 1500 DK', 425, 'BimCell', 'local', 'physical', 'BimCell', 12, null),
        $p('11', 'BimCell', 'BimCell Dost Ultra 20 GB + 750 DK', 445, 'BimCell', 'local', 'physical', 'BimCell', 20, null),
        $p('501', 'PttCell', 'PttCell SADE 5 GB', 100, 'PttCell', 'local', 'physical', 'PttCell', 5, null),
        $p('502', 'PttCell', 'PttCell SADE 10 GB', 126, 'PttCell', 'local', 'physical', 'PttCell', 10, null),
        $p('503', 'PttCell', 'PttCell SADE 20 GB', 172, 'PttCell', 'local', 'physical', 'PttCell', 20, null),
        $p('504', 'PttCell', 'PttCell SADE 30 GB', 199, 'PttCell', 'local', 'physical', 'PttCell', 30, null),
    ],
];

/*
 | Türk Telekom products at telekomtl also load on our Tele CARD lines (the "50" numbers): the team loads them
 | from telekomtl's panel on 50… numbers and «Transfer Takip» shows them «Yüklendi» (2026-11-24).
 | So a physical Turkey (Tele CARD) package is matched to them too, besides kontor86's Tele CARD products.
 */
$list['telekomtl'] = array_map(
    fn (array $product) => $product + [
        'tele_card_lines' => $product['group'] === 'Türk Telekom eSIM',
        // OnlineKart (panel tile «BARKOUD ESIM») is the eSIM the team loads for our «eSIM تركيا» (Tele CARD) orders,
        // on the customer's Turkish mobile number — owner's answer 2026-11-25.
        'tele_card_esim' => $product['group'] === 'OnlineKart eSIM (Türk Telekom)',
    ],
    $list['telekomtl']
);

return $list;
