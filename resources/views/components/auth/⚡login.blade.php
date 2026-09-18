<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
            'status' => 'active',
        ], $this->remember)) {
            $this->addError('email', 'The email address or password is incorrect.');
            return;
        }

        request()->session()->regenerate();

        $user = Auth::user();

        if (! $user instanceof User || ! $user->isActive()) {
            Auth::logout();

            $this->addError('email', 'This account is not active.');
            return;
        }

        $this->redirect($user->isPlatformAdmin() ? '/admin' : '/dashboard', navigate: true);
    }
};
?>

<div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900">
                TPX
            </h1>

            <p class="mt-2 text-gray-600">
                ThaiPropertyX Administration
            </p>
        </div>

        <div class="bg-white shadow-lg rounded-xl p-8">

            <h2 class="text-2xl font-semibold text-gray-900 mb-6">
                Sign in
            </h2>

            <form wire:submit="login">

                <div class="mb-5">
                    <label for="email"
                           class="block text-sm font-medium text-gray-700 mb-2">
                        Email address
                    </label>

                    <input
                        id="email"
                        type="email"
                        wire:model="email"
                        autocomplete="email"
                        autofocus
                        class="w-full rounded-lg border border-gray-300 px-4 py-3"
                    >

                    @error('email')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="password"
                           class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3"
                    >

                    @error('password')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="flex items-center mb-6">

                    <input
                        id="remember"
                        type="checkbox"
                        wire:model="remember"
                        class="rounded border-gray-300"
                    >

                    <label for="remember"
                           class="ml-2 text-sm text-gray-600">
                        Remember me
                    </label>

                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-gray-900 text-white py-3 px-4 font-semibold"
                >
                    Sign in
                </button>

            </form>

        </div>

        <p class="text-center text-sm text-gray-500 mt-6">
            ThaiPropertyX Property Exchange
        </p>

    </div>
</div>
