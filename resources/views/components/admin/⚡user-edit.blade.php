<?php

use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public User $user;

    public string $name = '';
    public string $email = '';
    public ?int $agency_id = null;
    public string $role = 'agent';
    public string $status = 'active';

    public function mount(User $user): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );

        $this->user = $user;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->agency_id = $user->agency_id;
        $this->role = $user->role;
        $this->status = $user->status;
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
        $isPlatformAdmin = $this->user->role === 'platform_admin';

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->user->id),
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
        ];

        if (!$isPlatformAdmin) {
            $rules['agency_id'] = [
                'required',
                'integer',
                Rule::exists('agencies', 'id'),
            ];

            $rules['role'] = [
                'required',
                Rule::in([
                    'agency_admin',
                    'agent',
                ]),
            ];
        }

        $validated = $this->validate($rules);

        $data = [
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'status' => $validated['status'],
        ];

        if (!$isPlatformAdmin) {
            $data['agency_id'] = $validated['agency_id'];
            $data['role'] = $validated['role'];
        }

        $this->user->update($data);

        session()->flash(
            'success',
            'User updated successfully.'
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

<x-admin-ui.layout title="Edit User">

    <div class="mx-auto max-w-6xl">

        {{-- BACK --}}
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
                Edit User
            </h3>

            <p class="mt-2 text-slate-500">
                {{ $user->name }}
            </p>

        </div>


        <form wire:submit="save">

            <div class="grid gap-7 lg:grid-cols-[minmax(0,1fr)_340px]">

                {{-- LEFT --}}
                <div class="space-y-7">

                    {{-- ACCOUNT DETAILS --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">

                        <div class="mb-7">

                            <h4 class="text-lg font-bold text-slate-900">
                                Account Details
                            </h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Update the user's identity and contact information.
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


                    {{-- SECURITY --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">

                        <div class="flex items-start gap-4">

                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    viewBox="0 0 24 24"
                                >
                                    <rect
                                        x="5"
                                        y="10"
                                        width="14"
                                        height="11"
                                        rx="2"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        d="M8 10V7a4 4 0 0 1 8 0v3"
                                    />
                                </svg>

                            </div>

                            <div>

                                <h4 class="text-base font-bold text-slate-900">
                                    Password Security
                                </h4>

                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    Passwords are stored securely and cannot be viewed
                                    from the administration area. Password changes will
                                    be handled through the secure password-reset system.
                                </p>

                            </div>

                        </div>

                    </section>

                </div>


                {{-- RIGHT --}}
                <div class="space-y-7">

                    @if ($user->role !== 'platform_admin')

                        {{-- AGENCY --}}
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                            <h4 class="text-base font-bold text-slate-900">
                                Agency Assignment
                            </h4>

                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                Assign this account to a participating TPX agency.
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

                    @else

                        {{-- PLATFORM ADMIN --}}
                        <section class="rounded-2xl border border-purple-200 bg-purple-50 p-6 shadow-sm">

                            <p class="text-xs font-bold uppercase tracking-wider text-purple-600">
                                Platform Account
                            </p>

                            <h4 class="mt-2 text-base font-bold text-purple-950">
                                Platform Administrator
                            </h4>

                            <p class="mt-2 text-sm leading-6 text-purple-700">
                                This account has platform-level access and is not
                                managed as a normal agency administrator or agent.
                            </p>

                        </section>

                    @endif


                    {{-- ACCESS --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <h4 class="text-base font-bold text-slate-900">
                            Access & Status
                        </h4>


                        @if ($user->role !== 'platform_admin')

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

                        @else

                            <div class="mt-6">

                                <p class="text-sm font-semibold text-slate-700">
                                    Account Role
                                </p>

                                <div class="mt-2 rounded-xl border border-purple-200 bg-purple-50 px-4 py-3.5 text-sm font-bold text-purple-700">
                                    Platform Administrator
                                </div>

                            </div>

                        @endif


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


                    {{-- ACCOUNT INFO --}}
                    <section class="rounded-2xl bg-slate-950 p-6 text-white shadow-sm">

                        <p class="text-xs font-bold uppercase tracking-wider text-amber-400">
                            Account Information
                        </p>


                        <dl class="mt-5 space-y-4">

                            <div class="flex items-center justify-between gap-4">

                                <dt class="text-sm text-slate-400">
                                    User ID
                                </dt>

                                <dd class="text-sm font-bold text-white">
                                    #{{ $user->id }}
                                </dd>

                            </div>


                            <div class="flex items-center justify-between gap-4">

                                <dt class="text-sm text-slate-400">
                                    Current Role
                                </dt>

                                <dd class="text-right text-sm font-bold text-white">

                                    @if ($user->role === 'platform_admin')
                                        Platform Admin
                                    @elseif ($user->role === 'agency_admin')
                                        Agency Admin
                                    @else
                                        Agent
                                    @endif

                                </dd>

                            </div>


                            <div class="flex items-center justify-between gap-4">

                                <dt class="text-sm text-slate-400">
                                    Current Status
                                </dt>

                                <dd class="text-sm font-bold text-white">
                                    {{ ucfirst($user->status) }}
                                </dd>

                            </div>


                            <div class="flex items-center justify-between gap-4">

                                <dt class="text-sm text-slate-400">
                                    Created
                                </dt>

                                <dd class="text-right text-sm font-bold text-white">
                                    {{ $user->created_at?->format('d M Y') ?? '—' }}
                                </dd>

                            </div>


                            <div class="flex items-center justify-between gap-4">

                                <dt class="text-sm text-slate-400">
                                    Email
                                </dt>

                                <dd class="text-right text-sm font-bold text-white">

                                    @if ($user->email_verified_at)
                                        Verified
                                    @else
                                        Not verified
                                    @endif

                                </dd>

                            </div>

                        </dl>

                    </section>

                </div>

            </div>


            {{-- ACTION BAR --}}
            <div class="mt-8 flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                <p class="text-sm text-slate-500">
                    Changes take effect when you update the account.
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

                        <span
                            wire:loading.remove
                            wire:target="save"
                        >
                            Update User
                        </span>

                        <span
                            wire:loading
                            wire:target="save"
                        >
                            Updating...
                        </span>

                    </button>

                </div>

            </div>

        </form>

    </div>

</x-admin-ui.layout>