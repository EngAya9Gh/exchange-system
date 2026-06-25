<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\DepositRequest;

class DepositFunds extends Component
{
    public $amount;
    public $method = 'sham';
    
    public $methods = [
        'sham' => [
            'name' => 'شام كاش',
            'logo' => 'sham.jpg',
            'details' => "عبد القادر العبد1\nالباركود: ef2dc825e5aa96bdb04c54499b9e1ff1",
        ],
        'uption' => [
            'name' => 'بنك ايش',
            'logo' => 'uption.png',
            'details' => "TR61 0006 4000 0011 0092 9912 59\nABDULKADER ALABD",
        ],
        'twasul' => [
            'name' => 'تواصل',
            'logo' => 'twasul.jpeg',
            'details' => "رقم الصندوق هو. 1174",
        ],
    ];

    public function submit()
    {
        $this->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|in:sham,twasul,uption',
        ], [
            'amount.required' => 'الرجاء إدخال المبلغ المحول.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من الصفر.',
        ]);

        $req = DepositRequest::create([
            'user_id' => auth()->id(),
            'amount' => $this->amount,
            'method' => $this->method,
            'receipt_image' => null,
            'status' => 'pending',
        ]);

        $methodName = $this->methods[$this->method]['name'];
        $text = "مرحباً، لقد قمت بتقديم طلب شحن برقم #{$req->id}\nالمبلغ: {$this->amount} TRY\nعبر: {$methodName}\nمرفق لكم صورة الإشعار البنكي:";
        $url = 'https://wa.me/905392065497?text=' . urlencode($text);

        $this->reset(['amount']);
        $this->method = 'sham';

        session()->flash('success_message', 'تم استلام طلبك بنجاح! سيتم فتح واتساب الآن لإرسال صورة الإشعار...');
        $this->js("window.open('{$url}', '_blank')");
    }

    public function render()
    {
        return view('livewire.customer.deposit-funds');
    }
}
