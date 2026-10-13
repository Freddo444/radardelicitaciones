<?php

namespace Tests\Feature;

use App\Rules\NotDisposableEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationSpamTest extends TestCase
{
    use RefreshDatabase;

    /** Verbatim from the four accounts that got through in production. */
    public static function spamNames(): array
    {
        return [
            'cyrillic + goo.su' => ['Вам перевод 102707 руб. получить тут  https://goo.su/EfLU4GeK THRT332726SVWVE'],
            'cyrillic + nip.io' => ['Вам перевод 163550 руб. получить тут  https://5a48ca64.nip.io/Eiof1fv9 JUYGTD44284JUYEGRT'],
            'cyrillic + sslip.io' => ['Вам перевод 108171 руб. получить тут  https://5a48ca64.sslip.io/2ffWgux7 JUYEGRT44284JUYEGRT'],
            'cyrillic + goo.su alt' => ['Вам перевод 185781 руб. забрать тут  https://goo.su/LgF0jLUv NFDAW44284TUJE'],
            // Same campaign with the script swapped — the link alone must stop it.
            'latin + link' => ['You received 102707 rub, claim at https://goo.su/EfLU4GeK'],
            'bare domain' => ['Claim here goo.su/EfLU4GeK now'],
            'telegram' => ['Contact t.me/spamchannel for details'],
            'www only' => ['www.example.com/offer'],
            'tracking token' => ['THRT332726SVWVE'],
        ];
    }

    #[DataProvider('spamNames')]
    public function test_spam_names_are_rejected_at_trial_signup(string $name): void
    {
        $response = $this->post(route('register.trial.store'), [
            'name' => $name,
            'email' => 'nuevo@empresa.do',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('users', 0);
    }

    public static function realNames(): array
    {
        return [
            ['Juan Pablo Fernández'],
            ['María José Peña Núñez'],
            ["D'Oleo Santana"],
            ['Jean-Carlos Abreu'],
            ['José Ramón de la Cruz Jr.'],
            ['Ana Sofía Ramírez Peña'],
        ];
    }

    #[DataProvider('realNames')]
    public function test_real_dominican_names_still_register(string $name): void
    {
        $response = $this->post(route('register.trial.store'), [
            'name' => $name,
            'email' => 'contacto'.bin2hex(random_bytes(4)).'@empresa.do',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['name' => $name]);
    }

    public function test_disposable_domains_are_refused_at_trial_signup(): void
    {
        $this->post(route('register.trial.store'), [
            'name' => 'Lisa Garcia',
            'email' => 'lisagarcia76238@smaqt.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_disposable_domains_are_refused_on_the_paid_path(): void
    {
        $this->post(route('register.create-order'), [
            'email' => 'raymondmurray25579@smaqt.com',
            'max_companies' => 1,
            'max_users' => 2,
        ])->assertSessionHasErrors('email');
    }

    public function test_the_disposable_list_is_shared_with_lifecycle_mail(): void
    {
        $this->assertTrue(NotDisposableEmail::isDisposable('x@smaqt.com'));
        $this->assertTrue(NotDisposableEmail::isDisposable('X@MAILINATOR.COM'));
        $this->assertFalse(NotDisposableEmail::isDisposable('fred@grupoalzare.com'));
        $this->assertFalse(NotDisposableEmail::isDisposable('sin-arroba'));
        $this->assertFalse(NotDisposableEmail::isDisposable(null));
    }
}
