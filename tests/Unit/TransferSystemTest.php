<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\ArabicNumberToWords;
use App\Services\CommissionCalculator;
use App\Services\SecretCodeGenerator;
use App\Models\CommissionTier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransferSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Arabic number to words conversion.
     */
    public function test_arabic_number_to_words_conversion(): void
    {
        $this->assertEquals('اثنان و عشرون جنيه مصري', ArabicNumberToWords::convert(22.0, 'جنيه مصري'));
        $this->assertEquals('مئة و خمسون ليرة تركية', ArabicNumberToWords::convert(150.0, 'ليرة تركية'));
        $this->assertEquals('ألف و مئتان دولار أمريكي', ArabicNumberToWords::convert(1200.0, 'دولار أمريكي'));
    }

    /**
     * Test commission calculator with fallback.
     */
    public function test_commission_calculator_fallback(): void
    {
        $calculator = new CommissionCalculator();
        
        // No tiers defined, should fallback to 2%
        $this->assertEquals(20.0, $calculator->calculate(1000.0));
    }

    /**
     * Test commission calculator with custom tiers.
     */
    public function test_commission_calculator_with_tiers(): void
    {
        $calculator = new CommissionCalculator();

        // Create a fixed commission tier
        CommissionTier::create([
            'region_id' => null,
            'min_amount' => 0,
            'max_amount' => 500,
            'commission_type' => 'fixed',
            'commission_value' => 15,
            'status' => 'active'
        ]);

        // Create a percentage commission tier
        CommissionTier::create([
            'region_id' => null,
            'min_amount' => 501,
            'max_amount' => 5000,
            'commission_type' => 'percentage',
            'commission_value' => 3, // 3%
            'status' => 'active'
        ]);

        // Should match fixed tier (15 TRY)
        $this->assertEquals(15.0, $calculator->calculate(300.0));

        // Should match percentage tier (3% of 1000 = 30)
        $this->assertEquals(30.0, $calculator->calculate(1000.0));
    }

    /**
     * Test secret code generator.
     */
    public function test_secret_code_generator(): void
    {
        $generator = new SecretCodeGenerator();
        $code = $generator->generate();

        $this->assertEquals(5, strlen($code));
        $this->assertTrue(is_numeric($code));
    }
}
