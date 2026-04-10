<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Setting;

class OnboardingService
{
    public static function getStatus(Company $company): array
    {
        $steps = [
            [
                'key' => 'company_profile',
                'title' => 'Completar perfil de empresa',
                'description' => 'Agrega representante legal e imágenes corporativas',
                'url' => route('empresa.index'),
                'completed' => (bool) ($company->rep_legal_nombre && ($company->logo_path || $company->firma_path)),
            ],
            [
                'key' => 'rubros',
                'title' => 'Configurar rubros',
                'description' => 'Verifica que tus rubros UNSPSC estén correctos',
                'url' => route('rubros.index'),
                'completed' => $company->rubros()->where('active', true)->exists(),
            ],
            [
                'key' => 'personnel',
                'title' => 'Agregar personal clave',
                'description' => 'Registra el personal técnico de tu empresa',
                'url' => route('personal.index'),
                'completed' => $company->personnel()->where('active', true)->exists(),
            ],
            [
                'key' => 'documents',
                'title' => 'Subir documentos habilitantes',
                'description' => 'Sube RNC, acta constitutiva y otros documentos',
                'url' => route('documentos.index'),
                'completed' => $company->vaultDocuments()->where('is_current', true)->exists(),
            ],
            [
                'key' => 'financials',
                'title' => 'Registrar información financiera',
                'description' => 'Agrega al menos un año fiscal con estados financieros',
                'url' => route('financiero.index'),
                'completed' => $company->financialRecords()->exists(),
            ],
            [
                'key' => 'notifications',
                'title' => 'Configurar notificaciones',
                'description' => 'Recibe alertas por correo o Telegram',
                'url' => route('settings.index'),
                'completed' => (bool) (Setting::get('notification_email', null, $company->id) || Setting::get('telegram_chat_id', null, $company->id)),
            ],
            [
                'key' => 'first_poll',
                'title' => 'Ejecutar primer sondeo',
                'description' => 'Escanea el portal DGCP en busca de procesos',
                'url' => route('dashboard').'#sondeo',
                'completed' => (bool) Setting::get('last_polled_at'),
            ],
        ];

        $completed = count(array_filter($steps, fn ($s) => $s['completed']));

        return [
            'dismissed' => (bool) $company->onboarding_dismissed_at,
            'steps' => $steps,
            'completed' => $completed,
            'total' => count($steps),
        ];
    }
}
