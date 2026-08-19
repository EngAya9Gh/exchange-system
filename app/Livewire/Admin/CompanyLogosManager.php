<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CompanySetting;
use App\Services\FaturaApiService;
use Illuminate\Support\Facades\Storage;

class CompanyLogosManager extends Component
{
    use WithFileUploads;

    public $companies = [];
    public $logos = [];
    public $existingLogos = [];

    public function mount(FaturaApiService $apiService)
    {
        // Fetch all companies from API
        $kurumlar = $apiService->kurumListesi();
        
        $this->companies = [];
        foreach ($kurumlar as $kurum) {
            $code = (int)($kurum['CompanyCode'] ?? $kurum['id'] ?? 0);
            if ($code) {
                $this->companies[] = [
                    'id' => $code,
                    'name' => $kurum['Name'] ?? $kurum['adi'] ?? 'غير معروف',
                ];
            }
        }

        $this->loadExistingLogos();
    }

    public function loadExistingLogos()
    {
        $settings = CompanySetting::all()->keyBy('company_code');
        $this->existingLogos = [];
        foreach ($settings as $code => $setting) {
            $this->existingLogos[$code] = $setting->logo_path;
        }
    }

    public function uploadLogo($companyCode)
    {
        if (!isset($this->logos[$companyCode])) {
            return;
        }

        $this->validate([
            "logos.{$companyCode}" => 'image|max:2048',
        ]);

        $path = $this->logos[$companyCode]->store('logos', 'public');
        $publicPath = 'storage/' . $path;

        CompanySetting::updateOrCreate(
            ['company_code' => $companyCode],
            ['logo_path' => $publicPath]
        );

        $this->logos[$companyCode] = null;
        $this->loadExistingLogos();
        
        session()->flash("success_{$companyCode}", 'تم رفع الشعار بنجاح.');
    }

    public function removeLogo($companyCode)
    {
        $setting = CompanySetting::where('company_code', $companyCode)->first();
        if ($setting && $setting->logo_path) {
            // Remove from storage
            $storagePath = str_replace('storage/', 'public/', $setting->logo_path);
            if (Storage::exists($storagePath)) {
                Storage::delete($storagePath);
            }
            $setting->delete();
            $this->loadExistingLogos();
            session()->flash("success_{$companyCode}", 'تم حذف الشعار.');
        }
    }

    public $search = '';

    public function render()
    {
        $filteredCompanies = collect($this->companies)->filter(function ($company) {
            if (empty($this->search)) return true;
            return mb_stripos($company['name'], $this->search) !== false;
        })->toArray();

        return view('livewire.admin.company-logos-manager', [
            'filteredCompanies' => $filteredCompanies,
        ]);
    }
}
