<?php

namespace App\Services\Billing;

/**
 * Azul Payment Page (Página de Pagos) — request AuthHash and form fields.
 *
 * @see https://dev.azul.com.do/ — Documento E-Commerce AZUL Página de Pagos (HMAC SHA-512, UTF-16LE)
 */
class AzulPaymentPageService
{
    public function paymentPageUrl(): string
    {
        return (string) config('services.azul.payment_page_url');
    }

    /**
     * @param  array{
     *   order_number:string,
     *   amount_cents:int,
     *   itbis_cents:int,
     *   approved_url:string,
     *   declined_url:string,
     *   cancel_url:string,
     *   use_custom_field1?:string,
     *   custom_field1_label?:string,
     *   custom_field1_value?:string,
     *   use_custom_field2?:string,
     *   custom_field2_label?:string,
     *   custom_field2_value?:string,
     *   show_transaction_result?:string,
     *   locale?:string,
     * }  $data
     * @return array<string, string>
     */
    public function buildSignedFormFields(array $data): array
    {
        $merchantId = (string) config('services.azul.merchant_id');
        $merchantName = (string) config('services.azul.merchant_name');
        $merchantType = (string) config('services.azul.merchant_type');
        $currencyCode = (string) config('services.azul.currency_code');
        $authKey = (string) config('services.azul.auth_key');

        $amountStr = $this->formatMinorUnits($data['amount_cents']);
        $itbisStr = $this->formatMinorUnits($data['itbis_cents']);
        $use1 = $data['use_custom_field1'] ?? '0';
        $label1 = $data['custom_field1_label'] ?? '';
        $value1 = $data['custom_field1_value'] ?? '';
        $use2 = $data['use_custom_field2'] ?? '0';
        $label2 = $data['custom_field2_label'] ?? '';
        $value2 = $data['custom_field2_value'] ?? '';
        $showTrx = $data['show_transaction_result'] ?? '0';
        $locale = $data['locale'] ?? 'ES';

        $fields = [
            'MerchantID' => $merchantId,
            'MerchantName' => $merchantName,
            'MerchantType' => $merchantType,
            'CurrencyCode' => $currencyCode,
            'OrderNumber' => $data['order_number'],
            'Amount' => $amountStr,
            'ITBIS' => $itbisStr,
            'ApprovedUrl' => $data['approved_url'],
            'DeclinedUrl' => $data['declined_url'],
            'CancelUrl' => $data['cancel_url'],
            'UseCustomField1' => $use1,
            'CustomField1Label' => $label1,
            'CustomField1Value' => $value1,
            'UseCustomField2' => $use2,
            'CustomField2Label' => $label2,
            'CustomField2Value' => $value2,
            'ShowTransactionResult' => $showTrx,
            'Locale' => $locale,
        ];

        $hashPayload = $merchantId.$merchantName.$merchantType.$currencyCode
            .$data['order_number'].$amountStr.$itbisStr
            .$data['approved_url'].$data['declined_url'].$data['cancel_url']
            .$use1.$label1.$value1.$use2.$label2.$value2
            .$authKey;

        $fields['AuthHash'] = $this->requestAuthHash($hashPayload, $authKey);
        $fields['AzulUrl'] = $this->paymentPageUrl();

        return $fields;
    }

    public function verifyResponseAuthHash(
        string $orderNumber,
        string $amount,
        string $authorizationCode,
        string $dateTime,
        string $responseCode,
        string $isoCode,
        string $responseMessage,
        string $errorDescription,
        string $rrn,
        string $authKey,
        string $receivedHashHex,
    ): bool {
        $line = $orderNumber.$amount.$authorizationCode.$dateTime.$responseCode.$isoCode
            .$responseMessage.$errorDescription.$rrn.$authKey;

        $utf16 = mb_convert_encoding($line, 'UTF-16LE', 'UTF-8');
        $expected = hash_hmac('sha512', $utf16, $authKey);

        return hash_equals(strtolower($expected), strtolower($receivedHashHex));
    }

    public function formatMinorUnits(int $cents): string
    {
        return (string) max(0, $cents);
    }

    /**
     * ITBIS portion (minor units) when total already includes 18% ITBIS.
     */
    public function itbisFromTotalInclusiveMinor(int $totalMinor): int
    {
        if ($totalMinor <= 0) {
            return 0;
        }

        return (int) round($totalMinor * 18 / 118);
    }

    public function usdToDopMinor(float $usd, float $rate): int
    {
        return (int) round($usd * $rate * 100);
    }

    private function requestAuthHash(string $concatenatedIncludingAuthKey, string $authKey): string
    {
        $utf16 = mb_convert_encoding($concatenatedIncludingAuthKey, 'UTF-16LE', 'UTF-8');

        return hash_hmac('sha512', $utf16, $authKey);
    }
}
