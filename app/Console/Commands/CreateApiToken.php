<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateApiToken extends Command
{
    protected $signature = 'api:create-token {email?} {--name=default}';
    protected $description = 'Create an API token for a user (creates user if not exists)';

    public function handle()
    {
        $email = $this->argument('email') ?? $this->ask('Enter email address');
        $tokenName = $this->option('name');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->info("User not found. Creating new user...");
            $name = $this->ask('Enter user name');
            $password = $this->secret('Enter password (or press enter for auto-generated)');

            if (empty($password)) {
                $password = bin2hex(random_bytes(8));
                $this->warn("Auto-generated password: {$password}");
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->info("✓ User created successfully!");
        }

        $token = $user->createToken($tokenName)->plainTextToken;

        $this->newLine();
        $this->info("====================================");
        $this->info("API Token generated successfully!");
        $this->info("====================================");
        $this->line("User: {$user->name} ({$user->email})");
        $this->line("Token Name: {$tokenName}");
        $this->newLine();
        $this->warn("⚠ SAVE THIS TOKEN - It won't be shown again!");
        $this->line($token);
        $this->newLine();
        $this->info("Add this to your Flutter app's API service:");
        $this->line("Authorization: Bearer {$token}");
        $this->info("====================================");

        return Command::SUCCESS;
    }
}
