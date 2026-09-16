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
    public string $listingStatus = '';
    public string $minPrice = '';
    public string $maxPrice = '';
    public string $cityArea = '';
    public string $province = '';
    public string $propertyType = '';
    public string $targetAgencyId = '';

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(Auth::check(), 403);

        /*
         * Normal agency users automatically act as their own agency.
         * Platform admins select an agency manually for testing/support.
         */
        if (
            !Auth::user()->isPlatformAdmin() &&
            Auth::user()->agency_id
        ) {
            $this->targetAgencyId = (string) Auth::user()->agency_id;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedListingStatus(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function updatedCityArea(): void
    {
        $this->resetPage();
    }

    public function updatedProvince(): void
    {
        $this->resetPage();
    }

    public function updatedPropertyType(): void
    {
        $this->resetPage();
    }

    public function updatedTargetAgencyId(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'listingStatus',
            'minPrice',
            'maxPrice',
            'cityArea',
            'province',
            'propertyType',
        ]);

        $this->resetPage();
    }

    private function resolveTargetAgency(): ?Agency
    {
        if (Auth::user()->isPlatformAdmin()) {
            if ($this->targetAgencyId === '') {
                return null;
            }

            return Agency::query()
                ->whereKey($this->targetAgencyId)
                ->where('status', 'active')
                ->first();
        }

        $agency = Auth::user()->agency;

        if (!$agency || $agency->status !== 'active') {
            return null;
        }

        return $agency;
    }

    public function addToMyWebsite(int $propertyId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $property = Property::query()
            ->with('agency')
            ->findOrFail($propertyId);

        if (!$property->exchange_available) {
            $this->errorMessage =
                'This property is no longer available to the TPX Network.';

            return;
        }

        if ($property->status !== 'active') {
            $this->errorMessage =
                'Only active properties can currently be syndicated.';

            return;
        }

        $targetAgency = $this->resolveTargetAgency();

        if (!$targetAgency) {
            $this->errorMessage = Auth::user()->isPlatformAdmin()
                ? 'Please select an active receiving agency.'
                : 'Your agency must be active before using the TPX Exchange.';

            return;
        }

        /*
         * The source agency must never syndicate its own property.
         */
        if ((int) $property->agency_id === (int) $targetAgency->id) {
            $this->errorMessage =
                'The source agency cannot syndicate its own property.';

            return;
        }

        /*
         * exchange_available is the source agency's permission.
         * No separate approval step is required.
         */
        $syndication = Syndication::query()->firstOrNew([
            'property_id' => $property->id,
            'target_agency_id' => $targetAgency->id,
        ]);

        $syndication->source_agency_id = $property->agency_id;
        $syndication->status = 'active';

        if (!$syndication->approved_at) {
            $syndication->approved_at = now();
        }

        /*
         * Reset activation time when a paused/revoked syndication
         * is added again.
         */
        $syndication->activated_at = now();
        $syndication->revoked_at = null;
        $syndication->save();

        $this->successMessage =
            $property->title .
            ' has been added to ' .
            $targetAgency->name .
            '.';
    }

    public function removeFromMyWebsite(int $propertyId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $property = Property::query()
            ->with('agency')
            ->findOrFail($propertyId);

        $targetAgency = $this->resolveTargetAgency();

        if (!$targetAgency) {
            $this->errorMessage = Auth::user()->isPlatformAdmin()
                ? 'Please select an active receiving agency.'
                : 'Your agency must be active before using the TPX Exchange.';

            return;
        }

        if ((int) $property->agency_id === (int) $targetAgency->id) {
            $this->errorMessage =
                'The source agency does not have a syndication copy to remove.';

            return;
        }

        $syndication = Syndication::query()
            ->where('property_id', $property->id)
            ->where('target_agency_id', $targetAgency->id)
            ->where('status', 'active')
            ->first();

        if (!$syndication) {
            $this->errorMessage =
                'This property is not currently active for ' .
                $targetAgency->name .
                '.';

            return;
        }

        /*
         * A target agency voluntarily removing a property pauses
         * only its own syndication. The source property remains
         * available to other TPX members.
         *
         * Source-agency withdrawal is different: that action revokes
         * syndications centrally.
         */
        $syndication->status = 'paused';
        $syndication->save();

        $this->successMessage =
            $property->title .
            ' has been removed from ' .
            $targetAgency->name .
            '.';
    }

    public function with(): array
    {
        $query = Property::query()
            ->with([
                'agency',
                'primaryImage',
                'syndications',
            ])
            ->withCount([
                'media',
                'syndications as active_syndications_count' => fn ($query) =>
                    $query->where('status', 'active'),
            ])
            ->where('exchange_available', true)
            ->where('status', 'active');

        if ($this->search !== '') {
            $search = trim($this->search);

            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%')
                    ->orWhere('development', 'like', '%' . $search . '%')
                    ->orWhere('district', 'like', '%' . $search . '%')
                    ->orWhere('province', 'like', '%' . $search . '%');
            });
        }

        /*
         * Listing status is intentionally separate from the property's
         * publication status. Exchange inventory itself is already limited
         * to active properties above.
         */
        if ($this->listingStatus === 'sale') {
            $query->where('for_sale', true);
        } elseif ($this->listingStatus === 'rent') {
            $query->where('for_rent', true);
        } elseif ($this->listingStatus === 'both') {
            $query
                ->where('for_sale', true)
                ->where('for_rent', true);
        }

        /*
         * City / Area searches across the location fields we currently
         * receive from TPX/Houzez. This allows searches such as Hua Hin,
         * Cha-Am, Khao Takiab and Pranburi without hard-coding place names.
         */
        if ($this->cityArea !== '') {
            $cityArea = trim($this->cityArea);

            $query->where(function ($subQuery) use ($cityArea) {
                $subQuery
                    ->where('district', 'like', '%' . $cityArea . '%')
                    ->orWhere('subdistrict', 'like', '%' . $cityArea . '%')
                    ->orWhere('development', 'like', '%' . $cityArea . '%')
                    ->orWhere('address', 'like', '%' . $cityArea . '%');
            });
        }

        /*
         * Price filtering follows the selected listing status.
         * With "All" or "For Sale & Rent", a property qualifies when either
         * its sale price or rental price falls inside the requested range.
         */
        $minPrice = is_numeric($this->minPrice)
            ? (float) $this->minPrice
            : null;

        $maxPrice = is_numeric($this->maxPrice)
            ? (float) $this->maxPrice
            : null;

        if ($minPrice !== null || $maxPrice !== null) {
            $applyPriceRange = function ($priceQuery, string $column) use ($minPrice, $maxPrice) {
                $priceQuery->whereNotNull($column);

                if ($minPrice !== null) {
                    $priceQuery->where($column, '>=', $minPrice);
                }

                if ($maxPrice !== null) {
                    $priceQuery->where($column, '<=', $maxPrice);
                }
            };

            if ($this->listingStatus === 'sale') {
                $query->where(function ($priceQuery) use ($applyPriceRange) {
                    $applyPriceRange($priceQuery, 'sale_price');
                });
            } elseif ($this->listingStatus === 'rent') {
                $query->where(function ($priceQuery) use ($applyPriceRange) {
                    $applyPriceRange($priceQuery, 'rental_price');
                });
            } else {
                $query->where(function ($priceQuery) use ($applyPriceRange) {
                    $priceQuery
                        ->where(function ($saleQuery) use ($applyPriceRange) {
                            $applyPriceRange($saleQuery, 'sale_price');
                        })
                        ->orWhere(function ($rentQuery) use ($applyPriceRange) {
                            $applyPriceRange($rentQuery, 'rental_price');
                        });
                });
            }
        }

        if ($this->province !== '') {
            $query->where('province', $this->province);
        }

        if ($this->propertyType !== '') {
            $query->where('property_type', $this->propertyType);
        }

        /*
         * Normal agency users do not see their own source inventory
         * as something they can add from the Exchange.
         */
        if (
            !Auth::user()->isPlatformAdmin() &&
            Auth::user()->agency_id
        ) {
            $query->where(
                'agency_id',
                '!=',
                Auth::user()->agency_id
            );
        }

        $activeAgencies = Agency::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $effectiveTargetAgencyId = Auth::user()->isPlatformAdmin()
            ? ($this->targetAgencyId !== '' ? (int) $this->targetAgencyId : null)
            : (Auth::user()->agency_id ? (int) Auth::user()->agency_id : null);

        return [
            'properties' => $query
                ->latest()
                ->paginate(20),

            'agencies' => $activeAgencies,

            'effectiveTargetAgencyId' => $effectiveTargetAgencyId,

            'provinces' => Property::query()
                ->where('exchange_available', true)
                ->where('status', 'active')
                ->whereNotNull('province')
                ->where('province', '!=', '')
                ->distinct()
                ->orderBy('province')
                ->pluck('province'),

            'propertyTypes' => Property::query()
                ->where('exchange_available', true)
                ->where('status', 'active')
                ->whereNotNull('property_type')
                ->where('property_type', '!=', '')
                ->distinct()
                ->orderBy('property_type')
                ->pluck('property_type'),

            'availableCount' => Property::query()
                ->where('exchange_available', true)
                ->where('status', 'active')
                ->count(),

            'activeSyndicationsCount' => Syndication::query()
                ->where('status', 'active')
                ->count(),
        ];
    }
};
?>

<x-admin-ui.layout title="TPX Exchange">

    <div class="space-y-6">

        {{-- HEADER --}}
        <div>
            <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                ThaiPropertyX Network
            </p>

            <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                TPX Exchange
            </h3>

            <p class="mt-2 max-w-3xl text-slate-500">
                Browse properties made available by TPX member agencies
                and add them to a participating agency's inventory.
            </p>
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


        {{-- STATS --}}
        <div class="grid gap-4 sm:grid-cols-2">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Available to Network
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ number_format($availableCount) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Active Syndications
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ number_format($activeSyndicationsCount) }}
                </p>
            </div>

        </div>


        {{-- PLATFORM ADMIN TARGET AGENCY --}}
        @if (Auth::user()->isPlatformAdmin())

            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">

                <div class="max-w-xl">

                    <label class="mb-2 block text-sm font-bold text-slate-800">
                        Admin: Syndicate as Agency
                    </label>

                    <select
                        wire:model.live="targetAgencyId"
                        class="w-full rounded-xl border border-amber-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">
                            Select receiving agency...
                        </option>

                        @foreach ($agencies as $agency)
                            <option value="{{ $agency->id }}">
                                {{ $agency->name }}
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-2 text-xs text-slate-600">
                        Platform administrator mode. Select the agency whose
                        TPX Exchange view and syndication state you want to test.
                    </p>

                </div>

            </section>

        @endif


        {{-- FILTERS --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

                <div class="xl:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Search
                    </label>

                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Title, reference, development or location..."
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Listing Status
                    </label>

                    <select
                        wire:model.live="listingStatus"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">All</option>
                        <option value="sale">For Sale</option>
                        <option value="rent">For Rent</option>
                        <option value="both">For Sale &amp; Rent</option>
                    </select>
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Property Type
                    </label>

                    <select
                        wire:model.live="propertyType"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">
                            All property types
                        </option>

                        @foreach ($propertyTypes as $type)
                            <option value="{{ $type }}">
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Min Price
                    </label>

                    <input
                        wire:model.live.debounce.400ms="minPrice"
                        type="number"
                        min="0"
                        step="1000"
                        placeholder="e.g. 3000000"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Max Price
                    </label>

                    <input
                        wire:model.live.debounce.400ms="maxPrice"
                        type="number"
                        min="0"
                        step="1000"
                        placeholder="e.g. 10000000"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        City / Area
                    </label>

                    <input
                        wire:model.live.debounce.300ms="cityArea"
                        type="text"
                        placeholder="Hua Hin, Cha-Am, Khao Takiab..."
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Province
                    </label>

                    <select
                        wire:model.live="province"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">
                            All provinces
                        </option>

                        @foreach ($provinces as $provinceName)
                            <option value="{{ $provinceName }}">
                                {{ $provinceName }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="mt-3 text-xs text-slate-500">
                Price filters follow the selected listing status. With All or For Sale &amp; Rent,
                TPX matches either the sale price or rental price.
            </div>


            @if (
                $search !== '' ||
                $listingStatus !== '' ||
                $minPrice !== '' ||
                $maxPrice !== '' ||
                $cityArea !== '' ||
                $province !== '' ||
                $propertyType !== ''
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


        {{-- EXCHANGE INVENTORY --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-5">

                <h4 class="text-lg font-semibold text-slate-900">
                    Available Properties
                </h4>

                <p class="mt-1 text-sm text-slate-500">
                    These properties have been authorised by their
                    source agency for immediate TPX syndication.
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full divide-y divide-slate-200" style="width: 100%;">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Property
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Source Agency
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Location
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Price
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Network
                            </th>

                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-200 bg-white">

                        @forelse ($properties as $property)

                            @php
                                $selectedAgencySyndication = $effectiveTargetAgencyId
                                    ? $property->syndications->first(
                                        fn ($syndication) =>
                                            (int) $syndication->target_agency_id === (int) $effectiveTargetAgencyId
                                    )
                                    : null;

                                $isAddedToSelectedAgency =
                                    $selectedAgencySyndication?->status === 'active';

                                $isOwnSourceProperty =
                                    $effectiveTargetAgencyId &&
                                    (int) $property->agency_id === (int) $effectiveTargetAgencyId;
                            @endphp

                            <tr class="transition hover:bg-slate-50">

                                {{-- PROPERTY --}}
                                <td class="px-6 py-5">

                                    <div class="flex items-center gap-4">

                                        <div
                                            class="shrink-0 overflow-hidden rounded-xl bg-slate-100"
                                            style="width: 96px; height: 80px;"
                                        >

                                            @if ($property->primaryImage)

                                                <img
                                                    src="{{ asset(ltrim($property->primaryImage->thumbnail_url ?: $property->primaryImage->url, '/')) }}"
                                                    alt="{{ $property->primaryImage->alt_text ?: $property->title }}"
                                                    style="width: 96px; height: 80px; object-fit: cover; display: block;"
                                                >

                                            @else

                                                <div class="flex h-full w-full items-center justify-center text-slate-400">
                                                    No image
                                                </div>

                                            @endif

                                        </div>


                                        <div>

                                            <p class="font-semibold text-slate-900">
                                                {{ $property->title }}
                                            </p>

                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ ucfirst($property->property_type) }}

                                                @if (!is_null($property->bedrooms))
                                                    · {{ $property->bedrooms }} bed
                                                @endif

                                                @if (!is_null($property->bathrooms))
                                                    · {{ $property->bathrooms }} bath
                                                @endif
                                            </p>

                                            @if ($property->reference)
                                                <p class="mt-1 text-xs text-slate-400">
                                                    Ref: {{ $property->reference }}
                                                </p>
                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- SOURCE --}}
                                <td class="px-6 py-5">

                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ $property->agency?->name ?: '—' }}
                                    </p>

                                </td>


                                {{-- LOCATION --}}
                                <td class="px-6 py-5">

                                    <p class="text-sm font-medium text-slate-700">
                                        {{ $property->district ?: '—' }}
                                    </p>

                                    <p class="mt-1 text-sm text-slate-400">
                                        {{ $property->province ?: '—' }}
                                    </p>

                                </td>


                                {{-- PRICE --}}
                                <td class="px-6 py-5">

                                    @if ($property->for_sale && $property->sale_price !== null)

                                        <p class="font-bold text-slate-900">
                                            {{ $property->currency }}
                                            {{ number_format((float) $property->sale_price, 0) }}
                                        </p>

                                        <p class="mt-1 text-xs font-semibold text-amber-600">
                                            For Sale
                                        </p>

                                    @endif


                                    @if ($property->for_rent && $property->rental_price !== null)

                                        <p class="{{ $property->for_sale ? 'mt-2' : '' }} font-bold text-slate-900">
                                            {{ $property->currency }}
                                            {{ number_format((float) $property->rental_price, 0) }}
                                        </p>

                                        <p class="mt-1 text-xs font-semibold text-sky-600">
                                            For Rent

                                            @if ($property->rental_period)
                                                / {{ $property->rental_period }}
                                            @endif
                                        </p>

                                    @endif

                                </td>


                                {{-- NETWORK --}}
                                <td class="px-6 py-5">

                                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        Available
                                    </span>

                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $property->active_syndications_count }}
                                        active
                                        {{ \Illuminate\Support\Str::plural('syndication', $property->active_syndications_count) }}
                                    </p>

                                    @if ($isAddedToSelectedAgency)
                                        <p class="mt-1 text-xs font-semibold text-emerald-700">
                                            Added to selected agency
                                        </p>
                                    @elseif ($selectedAgencySyndication?->status === 'paused')
                                        <p class="mt-1 text-xs font-semibold text-amber-700">
                                            Previously removed
                                        </p>
                                    @endif

                                </td>


                                {{-- ACTION --}}
                                <td class="px-6 py-5 text-right">

                                    <div class="flex flex-col items-end gap-2">

                                        @if (!$effectiveTargetAgencyId)

                                            <span class="text-xs font-semibold text-slate-400">
                                                Select an agency first
                                            </span>

                                        @elseif ($isOwnSourceProperty)

                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                Source Property
                                            </span>

                                        @elseif ($isAddedToSelectedAgency)

                                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                Added to My Website
                                            </span>

                                            <button
                                                type="button"
                                                wire:click="removeFromMyWebsite({{ $property->id }})"
                                                wire:confirm="Remove this property from the selected agency? The source property will remain available to the TPX Network."
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-100 disabled:cursor-wait disabled:opacity-50"
                                            >
                                                Remove from My Website
                                            </button>

                                        @else

                                            <button
                                                type="button"
                                                wire:click="addToMyWebsite({{ $property->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-amber-300 disabled:cursor-wait disabled:opacity-50"
                                            >
                                                Add to My Website
                                            </button>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-14 text-center"
                                >
                                    <p class="font-semibold text-slate-700">
                                        No properties are currently available.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Properties will appear here when
                                        a source agency enables TPX Network syndication.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if ($properties->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $properties->links() }}
                </div>
            @endif

        </section>

    </div>

</x-admin-ui.layout>
