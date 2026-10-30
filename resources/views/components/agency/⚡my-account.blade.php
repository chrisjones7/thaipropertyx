<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $whatsapp = '';
    public string $line_id = '';
    public string $job_title = '';
    public string $bio = '';

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->whatsapp = $user->whatsapp ?? '';
        $this->line_id = $user->line_id ?? '';
        $this->job_title = $user->job_title ?? '';
        $this->bio = $user->bio ?? '';
    }

    public function saveProfile(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:100'],
            'whatsapp' => ['nullable', 'string', 'max:100'],
            'line_id' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        $user->update($validated);

        session()->flash(
            'profile_success',
            'Your account details have been updated successfully.'
        );
    }

    public function changePassword(): void
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(
            'current_password',
            'password',
            'password_confirmation'
        );

        session()->flash(
            'password_success',
            'Your password has been changed successfully.'
        );
    }
};
?>

<x-agency-ui.layout title="My Account">

    <div class="max-w-6xl mx-auto space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">My Account</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage your personal details, contact information and password.
            </p>
        </div>

        @if (session('profile_success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('profile_success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2">
                <form wire:submit="saveProfile"
                      class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

                    <h2 class="text-lg font-semibold text-slate-900">Personal Details</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Your personal and contact information.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-6">

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                            <input type="text" wire:model="name"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                            <input type="email" wire:model="email"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                            <input type="text" wire:model="phone"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">WhatsApp</label>
                            <input type="text" wire:model="whatsapp"
                                   placeholder="+66..."
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            @error('whatsapp')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">LINE ID</label>
                            <input type="text" wire:model="line_id"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            @error('line_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Job Title</label>
                            <input type="text" wire:model="job_title"
                                   placeholder="e.g. Property Consultant"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                            @error('job_title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-5">
                        <label class="block text-sm font-medium text-slate-700 mb-1">About Me</label>
                        <textarea wire:model="bio" rows="4"
                                  placeholder="Short professional profile..."
                                  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"></textarea>
                        @error('bio')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                                class="px-8 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                            Save Details
                        </button>
                    </div>

                </form>
            </div>

            <div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

                    <h2 class="text-lg font-semibold text-slate-900">Account Information</h2>

                    <div class="mt-5 space-y-4">

                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Agency</div>
                            <div class="mt-1 font-medium text-slate-800">
                                {{ auth()->user()->agency?->name ?? 'Platform' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Role</div>
                            <div class="mt-1 font-medium text-slate-800">
                                {{ match(auth()->user()->role) {
                                    'platform_admin' => 'Platform Admin',
                                    'agency_admin' => 'Agency Admin',
                                    'agent' => 'Agent',
                                    default => ucfirst(str_replace('_', ' ', auth()->user()->role))
                                } }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Account Status</div>
                            <div class="mt-1">
                                @if (auth()->user()->isActive())
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        {{ ucfirst(auth()->user()->status) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <h2 class="text-lg font-semibold text-slate-900">Change Password</h2>
            <p class="mt-1 text-sm text-slate-500">
                Enter your current password before choosing a new password.
            </p>

            @if (session('password_success'))
                <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('password_success') }}
                </div>
            @endif

            <form wire:submit="changePassword" class="mt-6">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Current Password</label>
                        <input type="password" wire:model="current_password"
                               autocomplete="current-password"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        @error('current_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
                        <input type="password" wire:model="password"
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
                        <input type="password" wire:model="password_confirmation"
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    </div>

                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                        Change Password
                    </button>
                </div>

            </form>

        </div>

    </div>

</x-agency-ui.layout>