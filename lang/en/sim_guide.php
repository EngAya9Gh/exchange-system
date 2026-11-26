<?php

// «📘 Linking a package to the suppliers» in the packages manager (partials/package-link-guide)
return [
    'title' => 'Linking a package to the suppliers',
    'show' => 'Show the guide',
    'hide' => 'Hide',
    'intro' => 'Each supplier has three fields: operator, tip and code (API). With all three filled in, the package loads automatically from that supplier; left empty, it is never sent there.',

    'where_title' => '1. Where the package settings are',
    'where_1' => 'Dashboard → «Packages & Prices» → «Edit» on the package, or «Add Package» for a new one.',
    'where_2' => 'Top of the form: SIM type (physical or eSIM), GB, duration and price. HIZLI packages get «⚡ Fast package» ticked before saving.',
    'where_3' => 'The «Automatic loading» section at the bottom has a list per supplier. Picking the package there fills the three fields and shows the cost and profit.',

    'panel_title' => '2. A package missing from the list: values from the supplier panel',
    'panel_1' => 'Supplier panel → Kontor → ⚙️ «Kontor Fiyat Ayarları» → «Callback» button.',
    'panel_2' => 'The TARİFE column reads e.g. «Callback (TeleCard) HIZLI FİZİKSEL 50GB»: the first word is the operator (Callback), the word in brackets is the tip (TeleCard).',
    'panel_3' => 'The API column is the code; the ALIŞ column is what the package costs us.',
    'panel_4' => 'The number on the tiles of the loading page (like 250₺ or 2000₺) is a display price, not the code.',

    'examples_title' => '3. Examples from the supplier lists',
    'col_product' => 'Package at the supplier',
    'col_supplier' => 'Supplier',
    'col_operator' => 'Operator',
    'col_tip' => 'Tip',
    'col_code' => 'Code',
    'col_cost' => 'Cost',
    'col_for' => 'Our package',
    'for_physical' => 'Physical (Tele CARD line)',
    'for_fast' => '⚡ Fast',
    'for_esim' => 'Turkey eSIM',
    'for_europe' => 'Europe',

    'mistakes_title' => '4. Common mistakes',
    'mistake_1' => 'The «Package Name at the Supplier» field is a note only and is never sent; the code does not go there.',
    'mistake_2' => 'The three fields are filled in together or left empty together.',
    'mistake_3' => 'Letters are written exactly as on the panel: TeleCard, eSIM, OnlineKart.',
    'mistake_4' => '«⚡ Fast» packages are linked to kontor86 only; their telekomtl fields stay empty because telekomtl sells no HIZLI packages.',
    'mistake_5' => 'A new package saved with empty fields is linked to the product with the same type, GB and duration, so «⚡ Fast package» is ticked before saving.',
    'mistake_6' => '«The sale price is … below the supplier cost» means the package is linked to a product that costs more than its price.',
    'preferred' => 'An empty «Preferred supplier for this package» means: the primary supplier set in «Supplier connections».',
];
