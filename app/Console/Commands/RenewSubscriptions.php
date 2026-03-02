<?php

namespace App\Console\Commands;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:renew-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset monthly quotas for all clients when next_renewal_at is reached.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $clients = Client::where('next_renewal_at', '<=', now())->get();

        if ($clients->isEmpty()) {
            $this->info('No subscriptions to renew today.');

            return;
        }

        foreach ($clients as $client) {
            $this->info("Renewing subscription for: {$client->name}");

            $client->update([
                'sms_used_this_month' => 0,
                'email_used_this_month' => 0,
                'next_renewal_at' => Carbon::parse($client->next_renewal_at)->addMonth(),
            ]);

            Log::info("Subscription renewed for client: {$client->name}", [
                'client_id' => $client->id,
                'next_renewal' => $client->next_renewal_at,
            ]);
        }

        $this->info("Successfully renewed {$clients->count()} subscriptions.");
    }
}
