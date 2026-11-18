<?php

namespace Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\{Test, TestWith};
use Tests\TestCase;

class OrderingTranslationsTest extends TestCase
{
    #[Test]
    #[TestWith(['/'])]
    #[TestWith(['/meals'])]
    #[TestWith(['/my/orders'])]
    #[TestWith(['/my/balance'])]
    #[TestWith(['/meals/{meal}'])]
    public function english_users_receive_navigation_ordering_and_error_translations(string $path): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['settings' => ['language' => 'en']]);
        if ($path === '/meals/{meal}') {
            $path = '/meals/'.\App\Models\Meal::factory()->inFuture()->create()->id;
        }
        $this->login($user)->get($path)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'en')->where('translations.Bestellen', 'Order meals')
            ->where('translations.Auswahl anzeigen', 'Show selection')
            ->where('translations', function (\Illuminate\Support\Collection $translations): bool {
                $this->assertSame('Order confirmed by the server.', $translations['Bestellung vom Server bestätigt.']);
                $this->assertSame('You are offline', $translations['Sie sind offline']);
                $this->assertSame('The change was not confirmed. Check the overview and try again.',
                    $translations['Die Änderung wurde nicht bestätigt. Bitte prüfen Sie die Übersicht und versuchen Sie es erneut.']);

                return true;
            }));
    }

    #[Test]
    public function ordering_translation_keys_have_german_and_english_parity_without_empty_values(): void
    {
        $german = trans('ordering', [], 'de');
        $english = trans('ordering', [], 'en');
        $this->assertIsArray($german);
        $this->assertIsArray($english);
        $this->assertNotEmpty($german);
        $this->assertSame(array_keys($german), array_keys($english));
        foreach ($english as $key => $value) {
            $this->assertNotSame('', $value, $key);
            $this->assertSame($key, $german[$key]);
        }
    }
}
