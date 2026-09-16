<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $search = '';
    public string $role = 'all';
    public string $status = 'all';

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

    public function with(): array
    {
        $query = User::query()
            ->with('agency');

        if ($this->search !== '') {
            $search = '%' . $this->search . '%';

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhereHas('agency', function ($agencyQuery) use ($search) {
                        $agencyQuery->where('name', 'like', $search);
                    });
            });
        }

        if ($this->role !== 'all') {
            $query->where('role', $this->role);
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        return [
            'users' => $query
                ->orderBy('name')
                ->get(),

            'totalUsers' => User::count(),

            'agencyAdmins' => User::where(
                'role',
                'agency_admin'
            )->count(),

            'agents' => User::where(
                'role',
                'agent'
            )->count(),

            'activeUsers' => User::where(
                'status',
                'active'
            )->count(),
        ];
    }
};
?>

<x-admin-ui.layout title="Users & Agents">

    {{-- SUCCESS MESSAGE --}}
    @if (session('success'))

        <div class="mb-6 flex items-start gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">

            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">

                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m5 12 4 4L19 6"
                    />
                </svg>

            </div>

            <div class="pt-0.5">

                <p class="text-sm font-bold text-emerald-900">
                    Success
                </p>

                <p class="mt-1 text-sm text-emerald-700">
                    {{ session('success') }}
                </p>

            </div>

        </div>

    @endif


    {{-- PAGE HEADING --}}
    <div class="mb-7">

        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

            <div>

                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                    Network Accounts
                </p>

                <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    Users & Agents
                </h3>

                <p class="mt-2 max-w-2xl text-slate-500">
                    Manage platform administrators, agency administrators and
                    property agents participating in the ThaiPropertyX network.
                </p>

            </div>

        </div>

    </div>


    {{-- TOOLBAR --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

            <div class="flex flex-1 flex-col gap-3 md:flex-row">

                {{-- SEARCH --}}
                <div class="relative w-full md:max-w-md">

                    <svg
                        class="absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <circle cx="11" cy="11" r="7"/>
                        <path stroke-linecap="round" d="m20 20-4-4"/>
                    </svg>

                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search user, email or agency"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:bg-white"
                    >

                </div>


                {{-- ROLE --}}
                <select
                    wire:model.live="role"
                    class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-500 focus:bg-white"
                >
                    <option value="all">
                        All roles
                    </option>

                    <option value="platform_admin">
                        Platform Admin
                    </option>

                    <option value="agency_admin">
                        Agency Admin
                    </option>

                    <option value="agent">
                        Agent
                    </option>
                </select>


                {{-- STATUS --}}
                <select
                    wire:model.live="status"
                    class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-500 focus:bg-white"
                >
                    <option value="all">
                        All statuses
                    </option>

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

            </div>


            <div class="flex items-center justify-between gap-4 xl:justify-end">

                <p class="text-sm text-slate-500">

                    <span class="font-semibold text-slate-900">
                        {{ $users->count() }}
                    </span>

                    shown

                </p>


                {{-- ADD USER --}}
                <a
                    href="{{ route('admin.users.create') }}"
                    wire:navigate
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-amber-300"
                >

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 5v14M5 12h14"
                        />
                    </svg>

                    Add User

                </a>

            </div>

        </div>

    </div>


    {{-- STATISTICS --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">

        {{-- TOTAL USERS --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Total Users
                    </p>

                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $totalUsers }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- AGENCY ADMINS --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Agency Admins
                    </p>

                    <p class="mt-1 text-2xl font-bold text-blue-600">
                        {{ $agencyAdmins }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- AGENTS --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Agents
                    </p>

                    <p class="mt-1 text-2xl font-bold text-amber-600">
                        {{ $agents }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- ACTIVE USERS --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Active
                    </p>

                    <p class="mt-1 text-2xl font-bold text-emerald-600">
                        {{ $activeUsers }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m5 12 4 4L19 6"
                        />
                    </svg>

                </div>

            </div>

        </div>

    </div>


    {{-- USER DIRECTORY --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-5">

            <h4 class="text-lg font-semibold text-slate-900">
                User Directory
            </h4>

            <p class="mt-1 text-sm text-slate-500">
                Accounts with access to the ThaiPropertyX platform and agency network.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead class="bg-slate-50">

                    <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">

                        <th class="px-6 py-4 font-semibold">
                            User
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Agency
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Role
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Status
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Email Verified
                        </th>

                        <th class="px-6 py-4 text-right font-semibold">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($users as $user)

                        <tr class="transition hover:bg-slate-50/70">

                            {{-- USER --}}
                            <td class="px-6 py-5">

                                <div class="flex items-center gap-4">

                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white shadow-sm">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>


                                    <div class="min-w-0">

                                        <p class="font-semibold text-slate-900">
                                            {{ $user->name }}
                                        </p>

                                        <p class="mt-1 truncate text-sm text-slate-500">
                                            {{ $user->email }}
                                        </p>

                                    </div>

                                </div>

                            </td>


                            {{-- AGENCY --}}
                            <td class="px-6 py-5">

                                @if ($user->agency)

                                    <div class="text-sm font-semibold text-slate-700">
                                        {{ $user->agency->name }}
                                    </div>

                                    @if ($user->agency->province)

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $user->agency->province }}
                                        </div>

                                    @endif

                                @else

                                    @if ($user->role === 'platform_admin')

                                        <span class="text-sm font-medium text-slate-400">
                                            Platform
                                        </span>

                                    @else

                                        <span class="text-sm font-medium text-red-500">
                                            No agency assigned
                                        </span>

                                    @endif

                                @endif

                            </td>


                            {{-- ROLE --}}
                            <td class="px-6 py-5">

                                @php
                                    $roleClasses = match ($user->role) {
                                        'platform_admin' =>
                                            'bg-purple-50 text-purple-700 ring-purple-600/10',

                                        'agency_admin' =>
                                            'bg-blue-50 text-blue-700 ring-blue-600/10',

                                        'agent' =>
                                            'bg-amber-50 text-amber-700 ring-amber-600/10',

                                        default =>
                                            'bg-slate-100 text-slate-600 ring-slate-500/10',
                                    };

                                    $roleLabel = match ($user->role) {
                                        'platform_admin' =>
                                            'Platform Admin',

                                        'agency_admin' =>
                                            'Agency Admin',

                                        'agent' =>
                                            'Agent',

                                        default =>
                                            ucfirst($user->role),
                                    };
                                @endphp


                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $roleClasses }}"
                                >
                                    {{ $roleLabel }}
                                </span>

                            </td>


                            {{-- STATUS --}}
                            <td class="px-6 py-5">

                                @php
                                    $statusClasses = match ($user->status) {
                                        'active' =>
                                            'bg-emerald-50 text-emerald-700 ring-emerald-600/10',

                                        'pending' =>
                                            'bg-amber-50 text-amber-700 ring-amber-600/10',

                                        'suspended' =>
                                            'bg-red-50 text-red-700 ring-red-600/10',

                                        'disabled' =>
                                            'bg-slate-100 text-slate-600 ring-slate-500/10',

                                        default =>
                                            'bg-slate-100 text-slate-600 ring-slate-500/10',
                                    };
                                @endphp


                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}"
                                >
                                    {{ ucfirst($user->status) }}
                                </span>

                            </td>


                            {{-- EMAIL VERIFIED --}}
                            <td class="px-6 py-5">

                                @if ($user->email_verified_at)

                                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700">

                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-50">

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
                                                    d="m5 12 4 4L19 6"
                                                />
                                            </svg>

                                        </span>

                                        Verified

                                    </span>

                                @else

                                    <span class="text-sm font-medium text-slate-400">
                                        Not verified
                                    </span>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td class="px-6 py-5 text-right">

                                <a
                                    href="{{ route('admin.users.edit', $user) }}"
                                    wire:navigate
                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                                >

                                    Manage

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
                                            d="m9 18 6-6-6-6"
                                        />
                                    </svg>

                                </a>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-6 py-20 text-center"
                            >

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">

                                    <svg
                                        class="h-7 w-7"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8"
                                        />
                                    </svg>

                                </div>

                                <p class="mt-4 text-base font-semibold text-slate-900">
                                    No users found
                                </p>

                                <p class="mt-1 text-sm text-slate-500">
                                    Try changing your search or filters.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</x-admin-ui.layout>