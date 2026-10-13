<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\HumanName;
use App\Rules\NotDisposableEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PurgeSpamUsersCommand extends Command
{
    protected $signature = 'users:purge-spam
        {--dry-run : List what would be removed without deleting anything}
        {--include-disposable : Also remove accounts whose only flag is a throwaway mail domain}';

    protected $description = 'Remove signup-spam accounts: no company, no payment, and a name or address that fails the registration rules';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $candidates = User::query()
            ->where('is_super_admin', false)
            // Anyone who got as far as creating a company, or who ever paid, is
            // a real account whatever their name looks like.
            ->whereDoesntHave('companies')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('payments')
                    ->join('subscriptions', 'subscriptions.id', '=', 'payments.subscription_id')
                    ->whereColumn('subscriptions.user_id', 'users.id');
            })
            ->get()
            ->filter(fn (User $u) => $this->reasonFor($u) !== null);

        if ($candidates->isEmpty()) {
            $this->info('No spam accounts found.');

            return self::SUCCESS;
        }

        foreach ($candidates as $user) {
            $reason = $this->reasonFor($user);
            $label = sprintf('#%d %s — %s', $user->id, $user->email, $reason);

            if ($dryRun) {
                $this->line("[DRY] {$label}");
                $this->line('      name: '.mb_substr((string) $user->name, 0, 120));

                continue;
            }

            DB::transaction(function () use ($user) {
                DB::table('subscriptions')->where('user_id', $user->id)->delete();
                DB::table('users')->where('id', $user->id)->delete();
            });

            $this->info("Eliminada {$label}");
            Log::info('[PurgeSpam] account removed', [
                'user_id' => $user->id, 'email' => $user->email, 'reason' => $reason,
            ]);
        }

        $this->info($dryRun
            ? "Dry run: {$candidates->count()} cuenta(s) coinciden."
            : "Eliminadas {$candidates->count()} cuenta(s) de spam.");

        return self::SUCCESS;
    }

    /**
     * Reuses the registration rules so this command and the signup form can
     * never disagree about what counts as spam.
     *
     * A name carrying a link or a foreign script is unambiguous and is reason
     * enough on its own. A throwaway domain is not: that list exists to stop
     * marketing mail going somewhere it will bounce, which is a far lower bar
     * than deleting an account — atomicmail.io is a privacy provider a real
     * prospect might use. Sweeping those needs --include-disposable.
     */
    private function reasonFor(User $user): ?string
    {
        $nameFails = Validator::make(
            ['name' => (string) $user->name],
            ['name' => [new HumanName]]
        )->fails();

        if ($nameFails) {
            return 'nombre con payload';
        }

        if ($this->option('include-disposable') && NotDisposableEmail::isDisposable($user->email)) {
            return 'correo desechable';
        }

        return null;
    }
}
