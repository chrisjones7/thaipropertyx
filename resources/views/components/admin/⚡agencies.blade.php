<?php

use App\Models\Agency;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $search = '';
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
        $query = Agency::query()
            ->withCount(['properties', 'users']);

        if ($this->search !== '') {
            $search = '%' . $this->search . '%';

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('legal_name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('province', 'like', $search);
            });
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        return [
            'agencies' => $query
                ->orderBy('name')
                ->get(),

            'totalAgencies' => Agency::count(),
            'activeAgencies' => Agency::where('status', 'active')->count(),
            'pendingAgencies' => Agency::where('status', 'pending')->count(),
            'verifiedAgencies' => Agency::where('verified', true)->count(),
        ];
    }
};
?>

<x-admin-ui.layout title="Agencies">

    {{-- SUCCESS MESSAGE --}}
    @if (session('success'))
        <div
            class="mb-6 flex items-start gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm"
        >
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

        <div class="flex items-end justify-between gap-6">

            <div>

                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                    Exchange Network
                </p>

                <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    Agencies
                </h3>

                <p class="mt-2 max-w-2xl text-slate-500">
                    Manage agencies connected to ThaiPropertyX, monitor account status,
                    and review their property and agent activity.
                </p>

            </div>

        </div>

    </div>


    {{-- TOOLBAR --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

            <div class="flex flex-1 flex-col gap-3 sm:flex-row">

                {{-- SEARCH --}}
                <div class="relative w-full sm:max-w-md">

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
                        placeholder="Search by agency, email or location"
                        class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:bg-white"
                    >

                </div>


                {{-- STATUS FILTER --}}
                <select
                    wire:model.live="status"
                    class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-500 focus:bg-white"
                >
                    <option value="all">All statuses</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="suspended">Suspended</option>
                    <option value="disabled">Disabled</option>
                </select>

            </div>


            <div class="flex items-center justify-between gap-4 xl:justify-end">

                <p class="text-sm text-slate-500">
                    <span class="font-semibold text-slate-900">
                        {{ $agencies->count() }}
                    </span>
                    shown
                </p>


                <a
                    href="/admin/agencies/create"
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

                    Add Agency

                </a>

            </div>

        </div>

    </div>


    {{-- STATISTICS --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">

        {{-- TOTAL --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Total
                    </p>

                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $totalAgencies }}
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
                            d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- ACTIVE --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Active
                    </p>

                    <p class="mt-1 text-2xl font-bold text-emerald-600">
                        {{ $activeAgencies }}
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


        {{-- PENDING --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Pending
                    </p>

                    <p class="mt-1 text-2xl font-bold text-amber-600">
                        {{ $pendingAgencies }}
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
                            d="M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- VERIFIED --}}
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Verified
                    </p>

                    <p class="mt-1 text-2xl font-bold text-blue-600">
                        {{ $verifiedAgencies }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

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


    {{-- AGENCY DIRECTORY --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">

            <div>

                <h4 class="text-lg font-semibold text-slate-900">
                    Agency Directory
                </h4>

                <p class="mt-1 text-sm text-slate-500">
                    Participating organisations and their exchange activity.
                </p>

            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead class="bg-slate-50">

                    <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">

                        <th class="px-6 py-4 font-semibold">
                            Agency
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Location
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Listings
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Agents
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Status
                        </th>

                        <th class="px-6 py-4 font-semibold">
                            Verification
                        </th>

                        <th class="px-6 py-4 text-right font-semibold">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse ($agencies as $agency)

                        <tr class="group transition hover:bg-slate-50/70">

                            {{-- AGENCY --}}
                            <td class="px-6 py-5">

                                <div class="flex items-center gap-4">

                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-950 font-bold text-white shadow-sm">
                                        {{ strtoupper(substr($agency->name, 0, 1)) }}
                                    </div>

                                    <div class="min-w-0">

                                        <p class="font-semibold text-slate-900">
                                            {{ $agency->name }}
                                        </p>

                                        <p class="mt-1 truncate text-sm text-slate-500">
                                            {{ $agency->email ?: 'No email address' }}
                                        </p>

                                    </div>

                                </div>

                            </td>


                            {{-- LOCATION --}}
                            <td class="px-6 py-5 text-sm text-slate-600">

                                @if ($agency->district || $agency->province)

                                    <div class="font-medium text-slate-700">

                                        {{ collect([
                                            $agency->district,
                                            $agency->province
                                        ])->filter()->implode(', ') }}

                                    </div>

                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ $agency->country ?: 'Thailand' }}
                                    </div>

                                @else

                                    <span class="text-slate-400">
                                        Not specified
                                    </span>

                                @endif

                            </td>


                            {{-- LISTINGS --}}
                            <td class="px-6 py-5">

                                <div class="inline-flex min-w-10 items-center justify-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-800">
                                    {{ $agency->properties_count }}
                                </div>

                            </td>


                            {{-- AGENTS --}}
                            <td class="px-6 py-5">

                                <div class="inline-flex min-w-10 items-center justify-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-800">
                                    {{ $agency->users_count }}
                                </div>

                            </td>


                            {{-- STATUS --}}
                            <td class="px-6 py-5">

                                @php

                                    $statusClasses = match ($agency->status) {

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
                                    {{ ucfirst($agency->status) }}
                                </span>

                            </td>


                            {{-- VERIFICATION --}}
                            <td class="px-6 py-5">

                                @if ($agency->verified)

                                    <span class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700">

                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-50">

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
    href="{{ route('admin.agencies.edit', $agency) }}"
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
                                colspan="7"
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
                                            d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"
                                        />
                                    </svg>

                                </div>

                                <p class="mt-4 text-base font-semibold text-slate-900">
                                    No agencies found
                                </p>

                                <p class="mt-1 text-sm text-slate-500">
                                    Try changing your search or status filter.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</x-admin-ui.layout>