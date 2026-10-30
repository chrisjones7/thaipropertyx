<?php

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
    public string $direction = '';

    public ?string $successMessage = null;
    public ?string $errorMessage = null;


    public function mount(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->agency_id,
            403
        );

        if (request()->routeIs('agency.received-properties')) {
            $this->direction = 'target';
        }

        if (request()->routeIs('agency.shared-properties')) {
            $this->direction = 'source';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDirection(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'status',
        ]);

        $this->resetPage();
    }


    public function pausePropertySharing(int $propertyId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $agencyId = Auth::user()->agency_id;

        $property = Property::query()
            ->whereKey($propertyId)
            ->whereHas('syndications', function ($query) use ($agencyId) {
                $query->where('source_agency_id', $agencyId);
            })
            ->first();

        if (!$property) {
            $this->errorMessage = 'Shared property not found or access denied.';
            return;
        }

        $updated = Syndication::query()
            ->where('property_id', $propertyId)
            ->where('source_agency_id', $agencyId)
            ->where('status', 'active')
            ->update([
                'status' => 'paused',
            ]);

        if ($updated === 0) {
            $this->errorMessage = 'This property has no active syndications to pause.';
            return;
        }

        $this->successMessage =
            $property->title .
            ' has been paused for all receiving agencies.';
    }

    public function reactivatePropertySharing(int $propertyId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $agencyId = Auth::user()->agency_id;

        $property = Property::query()
            ->whereKey($propertyId)
            ->whereHas('syndications', function ($query) use ($agencyId) {
                $query->where('source_agency_id', $agencyId);
            })
            ->first();

        if (!$property) {
            $this->errorMessage = 'Shared property not found or access denied.';
            return;
        }

        if (!$property->exchange_available || $property->status !== 'active') {
            $this->errorMessage =
                'This property cannot be reactivated because it is no longer active and available to the TPX Network.';
            return;
        }

        $updated = Syndication::query()
            ->where('property_id', $propertyId)
            ->where('source_agency_id', $agencyId)
            ->where('status', 'paused')
            ->update([
                'status' => 'active',
            ]);

        if ($updated === 0) {
            $this->errorMessage = 'This property has no paused syndications to reactivate.';
            return;
        }

        $this->successMessage =
            $property->title .
            ' has been reactivated for all receiving agencies.';
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

        if ((int) $syndication->source_agency_id !== (int) Auth::user()->agency_id) {
            $this->errorMessage = 'Only the source agency can pause this syndication.';
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

        if ((int) $syndication->source_agency_id !== (int) Auth::user()->agency_id) {
            $this->errorMessage = 'Only the source agency can reactivate this syndication.';
            return;
        }

        if ($syndication->status !== 'paused') {
            $this->errorMessage = 'Only paused syndications can be reactivated.';
            return;
        }

        if (
            !$syndication->property->exchange_available ||
            $syndication->property->status !== 'active'
        ) {
            $this->errorMessage =
                'This syndication cannot be reactivated because the source property is no longer active and available to the TPX Network.';
            return;
        }

        $syndication->status = 'active';
        $syndication->save();

        $this->successMessage =
            'Syndication for ' .
            $syndication->property->title .
            ' has been reactivated for ' .
            $syndication->targetAgency->name .
            '.';
    }
    public function removeFromMyWebsite(int $syndicationId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $syndication = $this->findAuthorisedSyndication($syndicationId);

        if (!$syndication) {
            $this->errorMessage = 'Syndication record not found or access denied.';
            return;
        }

        /*
         * Only the receiving agency may voluntarily remove a syndicated
         * property from its own website.
         */
        if (
            !Auth::user()->isPlatformAdmin()
            && (int) $syndication->target_agency_id !== (int) Auth::user()->agency_id
        ) {
            $this->errorMessage = 'Only the receiving agency can remove this property from its website.';
            return;
        }

        if ($syndication->status === 'revoked') {
            $this->errorMessage = 'This syndication has been revoked by the source agency.';
            return;
        }

        if ($syndication->status === 'removed') {
            $this->errorMessage = 'This property has already been removed from your website.';
            return;
        }

        $syndication->status = 'removed';
        $syndication->save();

        $this->successMessage =
            $syndication->property->title .
            ' has been removed from your website.';
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
        $agencyId = Auth::user()->agency_id;

        /*
         * SHARED PROPERTIES
         *
         * One row per property, regardless of how many agencies
         * have syndicated that property.
         */
        if ($this->direction === 'source') {

            $propertyQuery = Property::query()
                ->with([
                    'primaryImage',
                ])
                ->whereHas('syndications', function ($query) use ($agencyId) {
                    $query->where('source_agency_id', $agencyId);
                });

            if ($this->search !== '') {
                $search = trim($this->search);

                $propertyQuery->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', '%' . $search . '%')
                        ->orWhere('reference', 'like', '%' . $search . '%')
                        ->orWhere('district', 'like', '%' . $search . '%')
                        ->orWhere('subdistrict', 'like', '%' . $search . '%')
                        ->orWhere('province', 'like', '%' . $search . '%')
                        ->orWhereHas('syndications.targetAgency', function ($agencyQuery) use ($search) {
                            $agencyQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            }

            if ($this->status !== '') {
                $propertyQuery->whereHas('syndications', function ($query) use ($agencyId) {
                    $query
                        ->where('source_agency_id', $agencyId)
                        ->where('status', $this->status);
                });
            }

            $propertyQuery
                ->with([
                    'syndications' => function ($query) use ($agencyId) {
                        $query
                            ->where('source_agency_id', $agencyId)
                            ->with('targetAgency')
                            ->latest('updated_at');
                    },
                ])
                ->withCount([
                    'syndications as receiving_agencies_count' => function ($query) use ($agencyId) {
                        $query->where('source_agency_id', $agencyId);
                    },

                    'syndications as active_syndications_count' => function ($query) use ($agencyId) {
                        $query
                            ->where('source_agency_id', $agencyId)
                            ->where('status', 'active');
                    },

                    'syndications as paused_syndications_count' => function ($query) use ($agencyId) {
                        $query
                            ->where('source_agency_id', $agencyId)
                            ->where('status', 'paused');
                    },
                ])
                ->latest('updated_at');

            $sharedStats = Syndication::query()
                ->where('source_agency_id', $agencyId);

            return [
                'sharedProperties' => $propertyQuery->paginate(20),

                'totalSharedProperties' => (clone $sharedStats)
                    ->distinct('property_id')
                    ->count('property_id'),

                'activeSyndications' => (clone $sharedStats)
                    ->where('status', 'active')
                    ->count(),

                /*
                 * Keep these variables available temporarily so the
                 * existing Blade does not fail while we replace its table.
                 */
                'syndications' => collect(),
                'totalSyndications' => (clone $sharedStats)
                    ->distinct('property_id')
                    ->count('property_id'),
            ];
        }

        /*
         * RECEIVED PROPERTIES
         *
         * Keep the existing syndication-per-row behaviour.
         */
        $query = Syndication::query()
            ->with([
                'property.primaryImage',
                'sourceAgency',
                'targetAgency',
            ])
            ->where('target_agency_id', $agencyId)
            ->latest('updated_at');

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
                    });
            });
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        $receivedStats = Syndication::query()
            ->where('target_agency_id', $agencyId);

        return [
            'syndications' => $query->paginate(20),

            'totalSyndications' => (clone $receivedStats)->count(),

            'activeSyndications' => (clone $receivedStats)
                ->where('status', 'active')
                ->count(),

            'sharedProperties' => collect(),
            'totalSharedProperties' => 0,
        ];
    }
};
?>

<div>
<x-agency-ui.layout :title="$direction === 'target' ? 'Received Properties' : 'Shared Properties'">

    <div class="space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">

            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-600">
                    TPX Network
                </p>

                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">
                    {{ $direction === 'target' ? 'Received Properties' : 'Shared Properties' }}
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    @if ($direction === 'target')
                        Properties your agency has received from other agencies through the TPX Property Network.
                    @else
                        Properties your agency has shared with other agencies through the TPX Property Network.
                    @endif
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
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">
                            {{ $direction === 'target' ? 'Total Received' : 'Total Shared' }}
                        </p>

                        <p class="mt-2 text-3xl font-bold text-slate-950">
                            {{ $totalSyndications }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" />
                        </svg>
                    </div>
                </div>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">
                            Active Syndications
                        </p>

                        <p class="mt-2 text-3xl font-bold text-emerald-700">
                            {{ $activeSyndications }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="8" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12 2.2 2.2 4.8-5" />
                        </svg>
                    </div>
                </div>
            </div>





        </div>


        {{-- FILTERS --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="grid gap-4 md:grid-cols-2">

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
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>



            </div>


            @if (
                $search !== '' ||
                $status !== ''
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


        @if ($direction === 'source')

        {{-- SHARED PROPERTIES - ONE ROW PER PROPERTY --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table style="width:100%;" class="divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Property
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Network Status
                            </th>

                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                                Shared With
                            </th>

                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                                Active Syndications
                            </th>

                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">

                        @forelse ($sharedProperties as $property)

                            @php
                                $image = $property->primaryImage;
                            @endphp

                            <tr class="align-top">

                                {{-- PROPERTY --}}
                                <td class="px-6 py-5">
                                    <div class="flex min-w-[300px] items-start gap-4">

                                        <div
                                            style="width:96px;height:80px;flex:0 0 96px;"
                                            class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
                                        >
                                            @if ($image)
                                                <img
                                                    src="{{ asset(ltrim($image->thumbnail_url ?: $image->url, '/')) }}"
                                                    alt="{{ $property->title }}"
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
                                                {{ $property->title }}
                                            </p>

                                            @if ($property->reference)
                                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                                    Ref: {{ $property->reference }}
                                                </p>
                                            @endif

                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ collect([
                                                    $property->subdistrict,
                                                    $property->district,
                                                    $property->province,
                                                ])->filter()->implode(', ') }}
                                            </p>
                                        </div>

                                    </div>
                                </td>

                                {{-- NETWORK STATUS --}}
                                <td class="px-6 py-5">
                                    @if ($property->exchange_available && $property->status === 'active')
                                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            Available to Network
                                        </span>
                                    @elseif (!$property->exchange_available)
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            Withdrawn from Network
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                                            Property {{ ucfirst($property->status) }}
                                        </span>
                                    @endif
                                </td>

                                {{-- SHARED WITH --}}
                                <td class="px-6 py-5 text-center">
                                    <p class="text-2xl font-bold text-slate-950">
                                        {{ $property->receiving_agencies_count }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $property->receiving_agencies_count === 1 ? 'agency' : 'agencies' }}
                                    </p>
                                </td>

                                {{-- ACTIVE SYNDICATIONS --}}
                                <td class="px-6 py-5 text-center">
                                    <p class="text-2xl font-bold text-emerald-700">
                                        {{ $property->active_syndications_count }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        currently active
                                    </p>
                                    <div class="mt-3">
                                <details class="w-full max-w-[340px]">
                                    <summary class="cursor-pointer list-none text-center text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                        View Agencies ({{ $property->receiving_agencies_count }})
                                    </summary>

                                    <div class="mt-3 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-5 text-left shadow-sm">

                                        @foreach ($property->syndications as $syndication)

                                            <div class="rounded-xl border border-slate-200 bg-white p-4">

                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-slate-900">
                                                            {{ $syndication->targetAgency?->name ?? 'Unknown agency' }}
                                                        </p>

                                                        @if ($syndication->status === 'active')
                                                            <span class="mt-2 inline-flex rounded-full bg-emerald-50 px-3.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                                Active
                                                            </span>
                                                        @elseif ($syndication->status === 'paused')
                                                            <span class="mt-2 inline-flex rounded-full bg-amber-50 px-3.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-200">
                                                                Paused
                                                            </span>
                                                        @elseif ($syndication->status === 'revoked')
                                                            <span class="mt-2 inline-flex rounded-full bg-red-50 px-3.5 py-1 text-xs font-bold text-red-700 ring-1 ring-inset ring-red-200">
                                                                Revoked
                                                            </span>
                                                        @elseif ($syndication->status === 'removed')
                                                            <span class="mt-2 inline-flex rounded-full bg-slate-100 px-3.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                Removed
                                                            </span>
                                                        @else
                                                            <span class="mt-2 inline-flex rounded-full bg-blue-50 px-3.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-200">
                                                                {{ ucfirst($syndication->status) }}
                                                            </span>
                                                        @endif
                                                    </div>


                                                </div>

                                                @if ($syndication->targetAgency)
                                                    <div class="mt-3 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">

                                                        @if ($syndication->targetAgency->phone)
                                                            <p>
                                                                <span class="font-semibold text-slate-700">Phone:</span>
                                                                <a href="tel:{{ $syndication->targetAgency->phone }}" class="text-blue-600 hover:underline">
                                                                    {{ $syndication->targetAgency->phone }}
                                                                </a>
                                                            </p>
                                                        @endif

                                                        @if ($syndication->targetAgency->email)
                                                            <p>
                                                                <span class="font-semibold text-slate-700">Email:</span>
                                                                <a href="mailto:{{ $syndication->targetAgency->email }}" class="break-all text-blue-600 hover:underline">
                                                                    {{ $syndication->targetAgency->email }}
                                                                </a>
                                                            </p>
                                                        @endif

                                                        @if ($syndication->targetAgency->website)
                                                            <p>
                                                                <span class="font-semibold text-slate-700">Website:</span>
                                                                <a href="{{ $syndication->targetAgency->website }}" target="_blank" rel="noopener noreferrer" class="break-all text-blue-600 hover:underline">
                                                                    {{ $syndication->targetAgency->website }}
                                                                </a>
                                                            </p>
                                                        @endif

                                                        <p class="mt-2 text-slate-400">
                                                            Syndicated {{ $syndication->created_at?->format('d M Y') }}
                                                        </p>
                                                    </div>
                                                @endif

                                            </div>

                                        @endforeach

                                    </div>
                                </details>
                                    </div>
                                </td>

                                {{-- ACTIONS / MANAGE AGENCIES --}}
                                <td class="px-6 py-5">
                                    <div class="flex min-w-[250px] flex-col items-end gap-3">

                                        <a
                                            href="{{ route('agency.properties.view', $property) }}"
                                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                        >
                                            View Property
                                        </a>

                                        @if ($property->active_syndications_count > 0)
                                            <button
                                                type="button"
                                                wire:click="pausePropertySharing({{ $property->id }})"
                                                wire:confirm="Pause sharing this property? This property will be paused for all receiving agencies."
                                                wire:loading.attr="disabled"
                                                class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-bold text-amber-700 transition hover:bg-amber-100 disabled:cursor-wait disabled:opacity-50"
                                            >
                                                Pause Sharing
                                            </button>

                                        @elseif ($property->paused_syndications_count > 0)
                                            <button
                                                type="button"
                                                wire:click="reactivatePropertySharing({{ $property->id }})"
                                                wire:confirm="Reactivate sharing this property? This property will be reactivated for all paused receiving agencies."
                                                wire:loading.attr="disabled"
                                                class="inline-flex cursor-pointer items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-wait disabled:opacity-50"
                                            >
                                                Reactivate Sharing
                                            </button>
                                        @endif


                                    </div>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center">
                                    <p class="font-semibold text-slate-700">
                                        No shared properties found.
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Properties will appear here when another agency adds one of your TPX Network properties to its website.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            @if ($sharedProperties->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $sharedProperties->links() }}
                </div>
            @endif

        </section>

        @else

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
                                                        <span class="inline-flex rounded-full bg-emerald-50 px-4 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                            Available to Network
                                                        </span>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-slate-100 px-4 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
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

                                    @if ((int) $syndication->target_agency_id === (int) Auth::user()->agency_id && $syndication->sourceAgency)
                                        <details class="relative mt-2">
                                            <summary class="cursor-pointer list-none text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                                Contact Agency
                                            </summary>

                                            <div class="mt-3 w-72 rounded-xl border border-slate-200 bg-white p-5 text-xs leading-5 text-slate-600 shadow-lg">
                                                <p class="text-sm font-bold text-slate-900">
                                                    {{ $syndication->sourceAgency->name }}
                                                </p>

                                                @if ($syndication->sourceAgency->phone)
                                                    <p class="mt-4">
                                                        <span class="font-semibold text-slate-700">Phone:</span><br>
                                                        <a href="tel:{{ $syndication->sourceAgency->phone }}" class="text-blue-600 hover:underline">
                                                            {{ $syndication->sourceAgency->phone }}
                                                        </a>
                                                    </p>
                                                @endif

                                                @if ($syndication->sourceAgency->email)
                                                    <p class="mt-3">
                                                        <span class="font-semibold text-slate-700">Email:</span><br>
                                                        <a href="mailto:{{ $syndication->sourceAgency->email }}" class="break-all text-blue-600 hover:underline">
                                                            {{ $syndication->sourceAgency->email }}
                                                        </a>
                                                    </p>
                                                @endif

                                                @if ($syndication->sourceAgency->website)
                                                    <p class="mt-3">
                                                        <span class="font-semibold text-slate-700">Website:</span><br>
                                                        <a href="{{ $syndication->sourceAgency->website }}" target="_blank" rel="noopener noreferrer" class="break-all text-blue-600 hover:underline">
                                                            {{ $syndication->sourceAgency->website }}
                                                        </a>
                                                    </p>
                                                @endif

                                                @if ($syndication->sourceAgency->address_line_1 || $syndication->sourceAgency->district || $syndication->sourceAgency->province)
                                                    <p class="mt-3">
                                                        <span class="font-semibold text-slate-700">Address:</span><br>
                                                        @if ($syndication->sourceAgency->address_line_1)
                                                            {{ $syndication->sourceAgency->address_line_1 }}<br>
                                                        @endif
                                                        @if ($syndication->sourceAgency->address_line_2)
                                                            {{ $syndication->sourceAgency->address_line_2 }}<br>
                                                        @endif
                                                        {{ collect([$syndication->sourceAgency->district, $syndication->sourceAgency->province])->filter()->join(', ') }}
                                                    </p>
                                                @endif
                                            </div>
                                        </details>
                                    @endif
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
                                            Active Syndications
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
                                                href="{{ route('agency.properties.view', $property) }}"
                                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                            >
                                                View Property
                                            </a>
                                        @endif


                                        @if ((int) $syndication->source_agency_id === (int) Auth::user()->agency_id)
                                            <span class="text-xs font-semibold text-slate-500">
                                                Source - your property
                                            </span>

                                            @if ($syndication->status === 'active')
                                                <button
                                                    type="button"
                                                    wire:click="pauseSyndication({{ $syndication->id }})"
                                                    wire:confirm="Pause this syndication? The receiving agency will no longer have this property active through this syndication until you reactivate it."
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex cursor-pointer items-center justify-center rounded-xl px-3.5 py-2 text-xs font-bold text-amber-600 transition hover:bg-amber-50 hover:text-amber-700 disabled:cursor-wait disabled:opacity-50"
                                                >
                                                    Pause
                                                </button>
                                            @elseif ($syndication->status === 'paused')
                                                <button
                                                    type="button"
                                                    wire:click="reactivateSyndication({{ $syndication->id }})"
                                                    wire:confirm="Reactivate this syndication? The property will become active again for the receiving agency."
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex cursor-pointer items-center justify-center rounded-xl px-3.5 py-2 text-xs font-bold text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-wait disabled:opacity-50"
                                                >
                                                    Reactivate
                                                </button>
                                            @endif
                                        @else
                                            <span class="text-xs font-semibold text-slate-500">
                                                Received - view only
                                            </span>
                                        @endif


                                        @if (
                                            (int) $syndication->target_agency_id === (int) Auth::user()->agency_id &&
                                            $syndication->status !== 'revoked' &&
                                            $syndication->status !== 'removed'
                                        )
                                            <button
                                                type="button"
                                                wire:click="removeFromMyWebsite({{ $syndication->id }})"
                                                wire:confirm="Remove this syndicated property from your website? You can add it again later from the TPX Property Network if it is still available."
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-50"
                                            >
                                                Remove from My Website
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
        @endif

    </div>

</x-agency-ui.layout>
</div>
