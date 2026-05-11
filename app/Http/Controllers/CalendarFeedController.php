<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Calendar\TableroIcalFeedService;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CalendarFeedController extends Controller
{
    public function tablero(string $token, TableroIcalFeedService $service): Response
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new NotFoundHttpException;
        }

        $company = Company::query()->where('calendar_feed_token', $token)->first();
        if (! $company) {
            throw new NotFoundHttpException;
        }

        $body = $service->render($company);

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
