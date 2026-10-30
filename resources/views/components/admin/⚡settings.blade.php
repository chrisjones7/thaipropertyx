
<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );
    }

    public function updatePassword(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($this->password);
        $user->save();

        $this->reset([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        session()->flash('password_success', 'Your password has been updated successfully.');
    }
};
?>

<x-admin-ui.layout title="Platform Settings">

    <div class="max-w-3xl">

        <div class="mb-8">
            <h3 class="text-3xl font-bold text-slate-900">
                Platform Settings
            </h3>

            <p class="mt-2 text-slate-500">
                Manage your ThaiPropertyX administrator account and security.
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

            <h4 class="text-xl font-bold text-slate-900 mb-2">
                Administrator Account
            </h4>

            <p class="text-sm text-slate-500 mb-6">
                Your platform administrator details.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

                <div>
                    <p class="text-sm text-slate-500">Name</p>
                    <p class="font-semibold text-slate-900">
                        {{ auth()->user()->name }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-slate-500">Email Address</p>
                    <p class="font-semibold text-slate-900">
                        {{ auth()->user()->email }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-slate-500">Account Role</p>
                    <p class="font-semibold text-slate-900">
                        Platform Administrator
                    </p>
                </div>

                <div>
                    <p class="text-sm text-slate-500">Account Status</p>
                    <p class="font-semibold text-emerald-600">
                        {{ ucfirst(auth()->user()->status) }}
                    </p>
                </div>

            </div>

            <div class="border-t border-slate-200 pt-8">

                <h4 class="text-xl font-bold text-slate-900 mb-2">
                    Change Password
                </h4>

                <p class="text-sm text-slate-500 mb-6">
                    Choose a new secure password for your administrator account.
                </p>

                @if (session()->has('password_success'))
                    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800">
                        {{ session('password_success') }}
                    </div>
                @endif

                <form wire:submit="updatePassword" class="space-y-5">

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Current Password
                        </label>
                        <input
                            type="password"
                            wire:model="current_password"
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            required
                        >
                        @error('current_password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            New Password
                        </label>
                        <input
                            type="password"
                            wire:model="password"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            required
                        >
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Confirm New Password
                        </label>
                        <input
                            type="password"
                            wire:model="password_confirmation"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="updatePassword"
                        class="rounded-xl bg-slate-950 text-white px-6 py-3 font-semibold hover:bg-slate-800 disabled:opacity-50 transition"
                    >
                        Update Password
                    </button>

                </form>

            </div>

        </div>

    </div>

</x-admin-ui.layout>
