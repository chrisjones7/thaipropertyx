<?php

use App\Models\Agency;
use App\Models\Property;
use App\Models\Syndication;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $sourceAgencyId = '';
    public string $targetAgencyId = '';

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(Auth::check(), 403);

        /*
         * This screen is intended as the central TPX syndication control panel.
         * Platform administrators can see all records.
         * Agency users only see records involving their own agency.
         */
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSourceAgencyId(): void
    {
        $this->resetPage();
    }

    public function updatedTargetAgencyId(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'status',
            'sourceAgencyId',
            'targetAgencyId',
        ]);

        $this->resetPage();
    }

    public function pauseSyndication(int $syndicationId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $syndication = $this->findAuthorisedSyndication($syndicationId);

        if (!$syndication) {
            $this->errorMessage = 'Syndication record not found or access denied.';
            return;
        }

        if ($syndication->status !== 'active') {
            $this->errorMessage = 'Only active syndications can be paused.';
            return;
        }

        $syndication->status = 'paused';
        $syndication->save();

        $this->successMessage =
            'Syndication for ' .
            $syndication->property->title .
            ' has been paused for ' .
            $syndication->targetAgency->name .
            '.';
    }

    public function reactivateSyndication(int $syndicationId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $syndication = $this->findAuthorisedSyndication($syndicationId);

        if (!$syndication) {
            $this->errorMessage = 'Syndication record not found or access denied.';
            return;
        }

        /*
         * A syndication can only be reactivated while the source property
         * remains active and available to the TPX Network.
         */
        if (
            !$syndication->property->exchange_available ||
            $syndication->property->status !== 'active'
        ) {
            $this->errorMessage =
                'This syndication cannot be reactivated because the source property is no longer active and available to the TPX Network.';
            return;
        }

        if ($syndication->status === 'active') {
            $this->errorMessage = 'This syndication is already active.';
            return;
        }

        $syndication->status = 'active';

        if (!$syndication->approved_at) {
            $syndication->approved_at = now();
        }

        $syndication->activated_at = now();
        $syndication->revoked_at = null;
        $syndication->save();

        $this->successMessage =
            'Syndication for ' .
            $syndication->property->title .
            ' has been reactivated for ' .
            $syndication->targetAgency->name .
            '.';
    }

    public function revokeSyndication(int $syndicationId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        /*
         * Manual revocation from the central control panel is deliberately
         * restricted to platform administrators.
         */
        if (!Auth::user()->isPlatformAdmin()) {
            $this->errorMessage = 'Only a platform administrator can revoke a syndication.';
            return;
        }

        $syndication = Syndication::query()
            ->with([
                'property',
                'sourceAgency',
                'targetAgency',
            ])
            ->find($syndicationId);

        if (!$syndication) {
            $this->errorMessage = 'Syndication record not found.';
            return;
        }

        if ($syndication->status === 'revoked') {
            $this->errorMessage = 'This syndication is already revoked.';
            return;
        }

        $syndication->status = 'revoked';
        $syndication->revoked_at = now();
        $syndication->save();

        $this->successMessage =
            'Syndication for ' .
            $syndication->property->title .
            ' has been revoked for ' .
            $syndication->targetAgency->name .
            '.';
    }

    private function findAuthorisedSyndication(int $syndicationId): ?Syndication
    {
        $query = Syndication::query()
            ->with([
                'property',
                'sourceAgency',
                'targetAgency',
            ])
            ->whereKey($syndicationId);

        if (!Auth::user()->isPlatformAdmin()) {
            $agencyId = Auth::user()->agency_id;

            if (!$agencyId) {
                return null;
            }

            $query->where(function ($subQuery) use ($agencyId) {
                $subQuery
                    ->where('source_agency_id', $agencyId)
                    ->orWhere('target_agency_id', $agencyId);
            });
        }

        return $query->first();
    }

    public function with(): array
    {
        $query = Syndication::query()
            ->with([
                'property.primaryImage',
                'sourceAgency',
                'targetAgency',
            ])
            ->latest('updated_at');

        /*
         * Agency members only see syndications involving their own agency.
         */
        if (!Auth::user()->isPlatformAdmin()) {
            $agencyId = Auth::user()->agency_id;

            if ($agencyId) {
                $query->where(function ($subQuery) use ($agencyId) {
                    $subQuery
                        ->where('source_agency_id', $agencyId)
                        ->orWhere('target_agency_id', $agencyId);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($this->search !== '') {
            $search = trim($this->search);

            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->whereHas('property', function ($propertyQuery) use ($search) {
                        $propertyQuery
                            ->where('title', 'like', '%' . $search . '%')
                            ->orWhere('reference', 'like', '%' . $search . '%')
                            ->orWhere('district', 'like', '%' . $search . '%')
                            ->orWhere('subdistrict', 'like', '%' . $search . '%')
                            ->orWhere('province', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('sourceAgency', function ($agencyQuery) use ($search) {
                        $agencyQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('targetAgency', function ($agencyQuery) use ($search) {
                        $agencyQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->sourceAgencyId !== '') {
            $query->where('source_agency_id', $this->sourceAgencyId);
        }

        if ($this->targetAgencyId !== '') {
            $query->where('target_agency_id', $this->targetAgencyId);
        }

        $agencies = Agency::query()
            ->orderBy('name')
            ->get();

        $statsBase = Syndication::query();

        if (!Auth::user()->isPlatformAdmin()) {
            $agencyId = Auth::user()->agency_id;

            if ($agencyId) {
                $statsBase->where(function ($subQuery) use ($agencyId) {
                    $subQuery
                        ->where('source_agency_id', $agencyId)
                        ->orWhere('target_agency_id', $agencyId);
                });
            } else {
                $statsBase->whereRaw('1 = 0');
            }
        }

        return [
            'syndications' => $query->paginate(20),
            'agencies' => $agencies,
            'totalSyndications' => (clone $statsBase)->count(),
            'activeSyndications' => (clone $statsBase)
                ->where('status', 'active')
                ->count(),
            'pausedSyndications' => (clone $statsBase)
                ->where('status', 'paused')
                ->count(),
            'revokedSyndications' => (clone $statsBase)
                ->where('status', 'revoked')
                ->count(),
        ];
    }
};
?>

<x-admin-ui.layout title="Syndication Management">

    <div class="space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">

            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-600">
                    TPX Network
                </p>

                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">
                    Syndication Management
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Monitor which TPX properties are being syndicated, which agency owns each listing,
                    which agency is receiving it, and the current syndication state.
                </p>
            </div>

        </div>


        {{-- MESSAGES --}}
        @if ($successMessage)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                {{ $successMessage }}
            </div>
        @endif

        @if ($errorMessage)
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-800">
                {{ $errorMessage }}
            </div>
        @endif


        {{-- SUMMARY --}}
        <div
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
        >

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">
                    Total Syndications
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-950">
                    {{ $totalSyndications }}
                </p>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">
                    Active
                </p>

                <p class="mt-2 text-3xl font-bold text-emerald-700">
                    {{ $activeSyndications }}
                </p>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">
                    Paused
                </p>

                <p class="mt-2 text-3xl font-bold text-amber-700">
                    {{ $pausedSyndications }}
                </p>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">
                    Revoked
                </p>

                <p class="mt-2 text-3xl font-bold text-red-700">
                    {{ $revokedSyndications }}
                </p>
            </div>

        </div>


        {{-- FILTERS --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Search
                    </label>

                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Property, reference, agency or location..."
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Status
                    </label>

                    <select
                        wire:model.live="status"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="revoked">Revoked</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>


                @if (Auth::user()->isPlatformAdmin())
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Source Agency
                        </label>

                        <select
                            wire:model.live="sourceAgencyId"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                        >
                            <option value="">All source agencies</option>

                            @foreach ($agencies as $agency)
                                <option value="{{ $agency->id }}">
                                    {{ $agency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Receiving Agency
                        </label>

                        <select
                            wire:model.live="targetAgencyId"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                        >
                            <option value="">All receiving agencies</option>

                            @foreach ($agencies as $agency)
                                <option value="{{ $agency->id }}">
                                    {{ $agency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

            </div>


            @if (
                $search !== '' ||
                $status !== '' ||
                $sourceAgencyId !== '' ||
                $targetAgencyId !== ''
            )
                <div class="mt-4">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="text-sm font-semibold text-slate-500 transition hover:text-slate-900"
                    >
                        Clear filters
                    </button>
                </div>
            @endif

        </section>


        {{-- TABLE --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table style="width:100%;" class="divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Property
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Source Agency
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Receiving Agency
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Status
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Dates
                            </th>

                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">
                                Actions
                            </th>
                        </tr>
                    </thead>


                    <tbody class="divide-y divide-slate-100 bg-white">

                        @forelse ($syndications as $syndication)

                            @php
                                $property = $syndication->property;
                                $image = $property?->primaryImage;
                            @endphp

                            <tr class="align-top">

                                {{-- PROPERTY --}}
                                <td class="px-6 py-5">

                                    <div class="flex min-w-[280px] items-start gap-4">

                                        <div
                                            style="width:96px;height:80px;flex:0 0 96px;"
                                            class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
                                        >
                                            @if ($image)
                                                <img
                                                    src="{{ asset(ltrim($image->thumbnail_url ?: $image->url, '/')) }}"
                                                    alt="{{ $property?->title }}"
                                                    style="width:96px;height:80px;object-fit:cover;"
                                                >
                                            @else
                                                <div
                                                    style="width:96px;height:80px;"
                                                    class="flex items-center justify-center text-xs font-semibold text-slate-400"
                                                >
                                                    No image
                                                </div>
                                            @endif
                                        </div>


                                        <div class="min-w-0">

                                            <p class="font-bold text-slate-900">
                                                {{ $property?->title ?? 'Property unavailable' }}
                                            </p>

                                            @if ($property?->reference)
                                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                                    Ref: {{ $property->reference }}
                                                </p>
                                            @endif

                                            @if ($property)
                                                <p class="mt-1 text-xs text-slate-500">
                                                    {{ collect([
                                                        $property->subdistrict,
                                                        $property->district,
                                                        $property->province,
                                                    ])->filter()->implode(', ') }}
                                                </p>

                                                <p class="mt-2">
                                                    @if ($property->exchange_available)
                                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                            Available to Network
                                                        </span>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                            Withdrawn from Network
                                                        </span>
                                                    @endif
                                                </p>
                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- SOURCE --}}
                                <td class="px-6 py-5">
                                    <p class="font-semibold text-slate-900">
                                        {{ $syndication->sourceAgency?->name ?? 'Unknown agency' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Owner / canonical source
                                    </p>
                                </td>


                                {{-- TARGET --}}
                                <td class="px-6 py-5">
                                    <p class="font-semibold text-slate-900">
                                        {{ $syndication->targetAgency?->name ?? 'Unknown agency' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Receiving / syndicating agency
                                    </p>
                                </td>


                                {{-- STATUS --}}
                                <td class="px-6 py-5">

                                    @if ($syndication->status === 'active')
                                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            Active
                                        </span>

                                    @elseif ($syndication->status === 'paused')
                                        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                                            Paused
                                        </span>

                                    @elseif ($syndication->status === 'revoked')
                                        <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700 ring-1 ring-inset ring-red-200">
                                            Revoked
                                        </span>

                                    @elseif ($syndication->status === 'approved')
                                        <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-200">
                                            Approved
                                        </span>

                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            {{ ucfirst($syndication->status) }}
                                        </span>
                                    @endif

                                </td>


                                {{-- DATES --}}
                                <td class="px-6 py-5">

                                    <div class="space-y-1 text-xs text-slate-500">

                                        <p>
                                            <span class="font-semibold text-slate-700">Created:</span>
                                            {{ $syndication->created_at?->format('d M Y, H:i') }}
                                        </p>

                                        @if ($syndication->activated_at)
                                            <p>
                                                <span class="font-semibold text-slate-700">Activated:</span>
                                                {{ $syndication->activated_at->format('d M Y, H:i') }}
                                            </p>
                                        @endif

                                        @if ($syndication->revoked_at)
                                            <p>
                                                <span class="font-semibold text-slate-700">Revoked:</span>
                                                {{ $syndication->revoked_at->format('d M Y, H:i') }}
                                            </p>
                                        @endif

                                    </div>

                                </td>


                                {{-- ACTIONS --}}
                                <td class="px-6 py-5 text-right">

                                    <div class="flex flex-col items-end gap-2">

                                        @if ($property)
                                            <a
                                                href="{{ route('admin.properties.edit', $property) }}"
                                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                            >
                                                View Property
                                            </a>
                                        @endif


                                        @if ($syndication->status === 'active')
                                            <button
                                                type="button"
                                                wire:click="pauseSyndication({{ $syndication->id }})"
                                                wire:confirm="Pause this syndication for the receiving agency? The source property will remain available to the TPX Network."
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100 disabled:cursor-wait disabled:opacity-50"
                                            >
                                                Pause
                                            </button>
                                        @endif


                                        @if (
                                            $syndication->status !== 'active' &&
                                            $syndication->status !== 'revoked' &&
                                            $property?->exchange_available &&
                                            $property?->status === 'active'
                                        )
                                            <button
                                                type="button"
                                                wire:click="reactivateSyndication({{ $syndication->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-wait disabled:opacity-50"
                                            >
                                                Reactivate
                                            </button>
                                        @endif


                                        @if (Auth::user()->isPlatformAdmin() && $syndication->status !== 'revoked')
                                            <button
                                                type="button"
                                                wire:click="revokeSyndication({{ $syndication->id }})"
                                                wire:confirm="Revoke this syndication? This is a central TPX administrative action."
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3.5 py-2 text-xs font-bold text-red-700 transition hover:bg-red-100 disabled:cursor-wait disabled:opacity-50"
                                            >
                                                Revoke
                                            </button>
                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <p class="font-semibold text-slate-700">
                                        No syndication records found.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Syndications will appear here when an agency adds a TPX Network property to its website.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if ($syndications->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $syndications->links() }}
                </div>
            @endif

        </section>

    </div>

</x-admin-ui.layout>
