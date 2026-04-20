<?php

namespace App\Services\Billing;

class AzulCheckoutBuilder
{
    public function __construct(
        private AzulPaymentPageService $paymentPage,
    ) {}

    /**
     * @return array{order_number: string, amount_cents: int, itbis_cents: int, amount_str: string, itbis_str: string}
     */
    public function forChargedUsd(float $chargedUsd): array
    {
        $rate = UsdDopExchange::rate();
        $totalMinor = $this->paymentPage->usdToDopMinor($chargedUsd, $rate);
        $itbisMinor = $this->paymentPage->itbisFromTotalInclusiveMinor($totalMinor);
        $orderNumber = substr(str_replace('.', '', uniqid('', true)), 0, 15);

        return [
            'order_number' => $orderNumber,
            'amount_cents' => $totalMinor,
            'itbis_cents' => $itbisMinor,
            'amount_str' => $this->paymentPage->formatMinorUnits($totalMinor),
            'itbis_str' => $this->paymentPage->formatMinorUnits($itbisMinor),
        ];
    }
}
