<?php

use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public string $role = 'agent';
    public string $status = 'active';

    public ?int $agency_id = null;

    public function mount(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/login', navigate: true);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'agency_id' => [
                'required',
                'integer',
                Rule::exists('agencies', 'id'),
            ],

            'role' => [
                'required',
                Rule::in([
                    'agency_admin',
                    'agent',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'active',
                    'suspended',
                    'disabled',
                ]),
            ],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'agency_id' => $validated['agency_id'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        session()->flash(
            'success',
            'User created successfully.'
        );

        $this->redirect(
            '/admin/users',
            navigate: true
        );
    }

    public function with(): array
    {
        return [
            'agencies' => Agency::query()
                ->whereIn('status', [
                    'active',
                    'pending',
                ])
                ->orderBy('name')
                ->get(),
        ];
    }
};
?>

<x-admin-ui.layout title="Add User">

    <div class="mx-auto max-w-6xl">

        {{-- BACK LINK --}}
        <div class="mb-6">

            <a
                href="/admin/users"
                wire:navigate
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m15 18-6-6 6-6"
                    />
                </svg>

                Back to Users & Agents
            </a>

        </div>


        {{-- HEADING --}}
        <div class="mb-8">

            <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                Network Account
            </p>

            <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                Add User
            </h3>

            <p class="mt-2 max-w-2xl text-slate-500">
                Create an agency administrator or property agent and assign
                them to a participating ThaiPropertyX agency.
            </p>

        </div>


        <form wire:submit="save">

            <div class="grid gap-7 lg:grid-cols-[minmax(0,1fr)_340px]">

                {{-- LEFT COLUMN --}}
                <div class="space-y-7">


                    {{-- ACCOUNT DETAILS --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">

                        <div class="mb-7">

                            <h4 class="text-lg font-bold text-slate-900">
                                Account Details
                            </h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Basic information used to identify and contact this user.
                            </p>

                        </div>


                        <div class="space-y-6">

                            {{-- NAME --}}
                            <div>

                                <label
                                    for="name"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Full Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="name"
                                    wire:model="name"
                                    type="text"
                                    autocomplete="name"
                                    placeholder="e.g. Somchai Prasert"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10"
                                >

                                @error('name')
                                    <p class="mt-2 text-sm font-medium text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- EMAIL --}}
                            <div>

                                <label
                                    for="email"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Email Address
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="email"
                                    wire:model="email"
                                    type="email"
                                    autocomplete="email"
                                    placeholder="agent@example.com"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10"
                                >

                                @error('email')
                                    <p class="mt-2 text-sm font-medium text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>

                        </div>

                    </section>


                    {{-- LOGIN SECURITY --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">

                        <div class="mb-7">

                            <h4 class="text-lg font-bold text-slate-900">
                                Login Security
                            </h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Set the initial password for this account.
                            </p>

                        </div>


                        <div class="grid gap-6 md:grid-cols-2">

                            {{-- PASSWORD --}}
                            <div>

                                <label
                                    for="password"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Password
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="password"
                                    wire:model="password"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="Minimum 8 characters"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10"
                                >

                                @error('password')
                                    <p class="mt-2 text-sm font-medium text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- CONFIRM PASSWORD --}}
                            <div>

                                <label
                                    for="password_confirmation"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Confirm Password
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="password_confirmation"
                                    wire:model="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    placeholder="Repeat password"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10"
                                >

                            </div>

                        </div>


                        <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 px-4 py-4">

                            <div class="flex gap-3">

                                <svg
                                    class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <circle cx="12" cy="12" r="9"/>
                                    <path stroke-linecap="round" d="M12 11v5M12 8h.01"/>
                                </svg>

                                <p class="text-sm leading-6 text-blue-800">
                                    This is the user's initial password. A password-reset
                                    workflow can be added later so agencies can manage
                                    credentials securely without platform administrators
                                    knowing their passwords.
                                </p>

                            </div>

                        </div>

                    </section>

                </div>


                {{-- RIGHT COLUMN --}}
                <div class="space-y-7">


                    {{-- AGENCY --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <h4 class="text-base font-bold text-slate-900">
                            Agency Assignment
                        </h4>

                        <p class="mt-1 text-sm leading-6 text-slate-500">
                            Every agency administrator and agent must belong to an agency.
                        </p>


                        <div class="mt-6">

                            <label
                                for="agency_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Agency
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="agency_id"
                                wire:model="agency_id"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10"
                            >
                                <option value="">
                                    Select agency
                                </option>

                                @foreach ($agencies as $agency)

                                    <option value="{{ $agency->id }}">
                                        {{ $agency->name }}
                                    </option>

                                @endforeach

                            </select>

                            @error('agency_id')
                                <p class="mt-2 text-sm font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </section>


                    {{-- ACCESS --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <h4 class="text-base font-bold text-slate-900">
                            Access & Status
                        </h4>


                        {{-- ROLE --}}
                        <div class="mt-6">

                            <label
                                for="role"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Account Role
                            </label>

                            <select
                                id="role"
                                wire:model="role"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10"
                            >
                                <option value="agent">
                                    Agent
                                </option>

                                <option value="agency_admin">
                                    Agency Administrator
                                </option>
                            </select>

                            @error('role')
                                <p class="mt-2 text-sm font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        {{-- STATUS --}}
                        <div class="mt-6">

                            <label
                                for="status"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Account Status
                            </label>

                            <select
                                id="status"
                                wire:model="status"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10"
                            >
                                <option value="active">
                                    Active
                                </option>

                                <option value="pending">
                                    Pending
                                </option>

                                <option value="suspended">
                                    Suspended
                                </option>

                                <option value="disabled">
                                    Disabled
                                </option>
                            </select>

                            @error('status')
                                <p class="mt-2 text-sm font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </section>


                    {{-- ROLE EXPLANATION --}}
                    <section class="rounded-2xl bg-slate-950 p-6 text-white shadow-sm">

                        <p class="text-xs font-bold uppercase tracking-wider text-amber-400">
                            TPX Permissions
                        </p>

                        <div class="mt-5 space-y-5">

                            <div>

                                <p class="text-sm font-bold text-white">
                                    Agency Administrator
                                </p>

                                <p class="mt-1 text-sm leading-6 text-slate-400">
                                    Intended to manage their agency's account, agents,
                                    listings and syndication activity.
                                </p>

                            </div>

                            <div class="border-t border-slate-800 pt-5">

                                <p class="text-sm font-bold text-white">
                                    Agent
                                </p>

                                <p class="mt-1 text-sm leading-6 text-slate-400">
                                    Intended for individual property agents working
                                    within the assigned agency.
                                </p>

                            </div>

                        </div>

                    </section>

                </div>

            </div>


            {{-- ACTION BAR --}}
            <div class="mt-8 flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                <p class="text-sm text-slate-500">
                    Fields marked
                    <span class="font-bold text-red-500">*</span>
                    are required.
                </p>


                <div class="flex gap-3">

                    <a
                        href="/admin/users"
                        wire:navigate
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex min-w-36 items-center justify-center gap-2 rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60"
                    >

                        <span wire:loading.remove wire:target="save">
                            Create User
                        </span>

                        <span wire:loading wire:target="save">
                            Creating...
                        </span>

                    </button>

                </div>

            </div>

        </form>

    </div>

</x-admin-ui.layout>