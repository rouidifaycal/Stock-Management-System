<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('stockflow:bootstrap-administrator {--name=} {--email=} {--confirm : Confirm first-administrator setup in a local environment}')]
#[Description('Create the first StockFlow Administrator in a local environment')]
class BootstrapAdministrator extends Command
{
    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Administrator bootstrap is available only in the local environment.');

            return self::FAILURE;
        }

        if (User::query()->where('role', UserRole::Administrator->value)->exists()) {
            $this->info('Administrator bootstrap is already complete; no changes were made.');

            return self::SUCCESS;
        }

        if (! $this->option('confirm')) {
            $this->error('Pass --confirm to explicitly bootstrap the first Administrator.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive() && (! $this->option('name') || ! $this->option('email'))) {
            $this->error('Interactive input is required unless --name and --email are provided.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Administrator name');
        $email = $this->option('email') ?: $this->ask('Administrator email');
        $password = $this->secret('Administrator password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => [
                'required',
                'string',
                'max:255',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();

        try {
            $created = DB::transaction(function () use ($data): bool {
                $administrators = User::query()
                    ->where('role', UserRole::Administrator->value)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id']);

                if ($administrators->isNotEmpty()) {
                    return false;
                }

                $user = new User([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                ]);
                $user->role = UserRole::Administrator;
                $user->save();

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            $this->error('The Administrator email is already in use; no account was changed.');

            return self::FAILURE;
        }

        if (! $created) {
            $this->info('Administrator bootstrap is already complete; no changes were made.');

            return self::SUCCESS;
        }

        $this->info('The first Administrator account was created.');

        return self::SUCCESS;
    }
}
