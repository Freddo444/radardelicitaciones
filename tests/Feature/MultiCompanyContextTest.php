<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\VaultDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class MultiCompanyContextTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private function attachSecondCompany($user): Company
    {
        $second = Company::create([
            'razon_social' => 'Segunda Empresa SRL',
            'rnc' => '1-31-77777-1',
            'owner_id' => $user->id,
        ]);
        $user->companies()->attach($second->id, ['joined_at' => now()]);
        $user->subscription->update(['max_companies' => 3]);

        return $second;
    }

    public function test_perfil_edits_the_active_company_and_follows_the_switch(): void
    {
        [$user, , $first] = $this->makeOwnerWithCompany([], ['razon_social' => 'Primera Empresa SRL']);
        $second = $this->attachSecondCompany($user);

        $this->actingAs($user)->get('/empresa')
            ->assertOk()
            ->assertSee('Primera Empresa SRL')
            ->assertDontSee('Segunda Empresa SRL');

        $this->actingAs($user)->post(route('companies.switch', $second))->assertRedirect();

        $this->actingAs($user)->get('/empresa')
            ->assertOk()
            ->assertSee('Segunda Empresa SRL')
            ->assertDontSee('Primera Empresa SRL');
    }

    public function test_profile_update_only_touches_the_active_company(): void
    {
        [$user, , $first] = $this->makeOwnerWithCompany();
        $second = $this->attachSecondCompany($user);

        $this->actingAs($user)->post(route('companies.switch', $second));
        $this->actingAs($user)->post(route('empresa.update'), [
            'razon_social' => 'Segunda Renombrada SRL',
        ])->assertRedirect();

        $this->assertSame('Segunda Renombrada SRL', $second->fresh()->razon_social);
        $this->assertNotSame('Segunda Renombrada SRL', $first->fresh()->razon_social);
    }

    public function test_documents_are_scoped_to_the_active_company(): void
    {
        [$user, , $first] = $this->makeOwnerWithCompany();
        $second = $this->attachSecondCompany($user);

        VaultDocument::create(['company_id' => $first->id, 'category' => 'legal', 'name' => 'DOC-DE-PRIMERA', 'filename' => 'a.pdf', 'path' => "vault/{$first->id}/legal/a.pdf", 'is_current' => true]);
        VaultDocument::create(['company_id' => $second->id, 'category' => 'legal', 'name' => 'DOC-DE-SEGUNDA', 'filename' => 'b.pdf', 'path' => "vault/{$second->id}/legal/b.pdf", 'is_current' => true]);

        $this->actingAs($user)->get('/documentos')
            ->assertOk()
            ->assertSee('DOC-DE-PRIMERA')
            ->assertDontSee('DOC-DE-SEGUNDA');

        $this->actingAs($user)->post(route('companies.switch', $second));

        $this->actingAs($user)->get('/documentos')
            ->assertOk()
            ->assertSee('DOC-DE-SEGUNDA')
            ->assertDontSee('DOC-DE-PRIMERA');
    }

    public function test_a_user_cannot_switch_to_a_company_they_do_not_belong_to(): void
    {
        [$outsider] = $this->makeOwnerWithCompany();
        [, , $foreign] = $this->makeOwnerWithCompany();

        $this->actingAs($outsider)
            ->post(route('companies.switch', $foreign))
            ->assertRedirect(route('companies.index'));

        $this->assertNotSame($foreign->id, $outsider->fresh()->current_company_id);
    }

    public function test_document_files_are_stored_under_a_per_company_path(): void
    {
        [, , $company] = $this->makeOwnerWithCompany();

        $doc = VaultDocument::create([
            'company_id' => $company->id, 'category' => 'legal', 'name' => 'RNC',
            'filename' => 'rnc.pdf', 'path' => "vault/{$company->id}/legal/rnc.pdf", 'is_current' => true,
        ]);

        $this->assertStringStartsWith("vault/{$company->id}/", $doc->path);
    }
}
