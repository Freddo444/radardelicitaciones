<?php

namespace Tests\Unit;

use App\Services\Billing\AzulPaymentPageService;
use Tests\TestCase;

class AzulPaymentPageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.azul.merchant_id' => '39038540035',
            'services.azul.auth_key' => 'test-secret-key',
            'services.azul.merchant_name' => 'Test Merchant',
            'services.azul.merchant_type' => 'Comercio electronico',
            'services.azul.currency_code' => '$',
            'services.azul.payment_page_url' => 'https://pruebas.azul.com.do/paymentpage/Default.aspx',
        ]);
    }

    public function test_build_signed_form_fields_includes_auth_hash_and_azul_url(): void
    {
        $svc = new AzulPaymentPageService;

        $fields = $svc->buildSignedFormFields([
            'order_number' => 'ORD12345678901',
            'amount_cents' => 265500,
            'itbis_cents' => 40500,
            'approved_url' => 'https://example.com/ok',
            'declined_url' => 'https://example.com/no',
            'cancel_url' => 'https://example.com/cancel',
        ]);

        $this->assertArrayHasKey('AuthHash', $fields);
        $this->assertSame(128, strlen($fields['AuthHash']));
        $this->assertSame('https://pruebas.azul.com.do/paymentpage/Default.aspx', $fields['AzulUrl']);
        $this->assertSame('39038540035', $fields['MerchantID']);
        $this->assertSame('265500', $fields['Amount']);
        $this->assertSame('40500', $fields['ITBIS']);
    }

    public function test_verify_response_auth_hash_round_trip(): void
    {
        $svc = new AzulPaymentPageService;
        $key = 'test-secret-key';

        $orderNumber = 'ORD1';
        $amount = '1000';
        $authorizationCode = 'AUTH1';
        $dateTime = '2026-01-01 12:00:00';
        $responseCode = '1';
        $isoCode = '00';
        $responseMessage = 'APROBADA';
        $errorDescription = '';
        $rrn = 'RRN1';

        $line = $orderNumber.$amount.$authorizationCode.$dateTime.$responseCode.$isoCode
            .$responseMessage.$errorDescription.$rrn.$key;
        $utf16 = mb_convert_encoding($line, 'UTF-16LE', 'UTF-8');
        $hash = hash_hmac('sha512', $utf16, $key);

        $this->assertTrue($svc->verifyResponseAuthHash(
            $orderNumber,
            $amount,
            $authorizationCode,
            $dateTime,
            $responseCode,
            $isoCode,
            $responseMessage,
            $errorDescription,
            $rrn,
            $key,
            $hash,
        ));
    }

    public function test_itbis_from_total_inclusive_minor(): void
    {
        $svc = new AzulPaymentPageService;

        $this->assertSame(40500, $svc->itbisFromTotalInclusiveMinor(265500));
    }
}
