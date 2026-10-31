<?php

use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $agency;

public string $search = '';
public string $status = '';
public string $listingType = '';
public string $minPrice = '';
public string $maxPrice = '';
public string $propertyType = '';
public string $province = '';

    public function mount(): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless(
            $user->isAgencyAdmin() || $user->isAgent(),
            403
        );

        abort_unless($user->agency_id, 403);

        $this->agency = $user->agency;

        abort_unless($this->agency, 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedListingType(): void
    {
        $this->resetPage();
    }
    public function updatedPropertyType(): void
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

    public function updatedProvince(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
 $this->reset([
    'search',
    'status',
    'listingType',
    'propertyType',
    'minPrice',
    'maxPrice',
    'province',
]);
        $this->resetPage();
    }

    public function toggleNetworkAvailability(int $propertyId): void
    {
        $agencyId = $this->agency->id;

        $property = Property::query()
            ->where('agency_id', $agencyId)
            ->where('status', '!=', 'withdrawn')
            ->findOrFail($propertyId);

        $now = now();

        /*
         * Property has never been added to the TPX Network.
         */
        if (!$property->exchange_available) {
            $property->update([
                'exchange_available' => true,
                'exchange_available_at' => $now,
                'exchange_withdrawn_at' => null,
            ]);

            session()->flash(
                'success',
                'Property added to the TPX Network and is now available to authorised agencies.'
            );

            return;
        }

        /*
         * Resume previously paused sharing.
         */
        if ($property->syndications()->where('status', 'paused')->exists()) {
            $property->syndications()
                ->where('status', 'paused')
                ->update([
                    'status' => 'active',
                    'revoked_at' => null,
                    'updated_at' => $now,
                ]);

            session()->flash(
                'success',
                'Property sharing resumed. Receiving websites will reactivate their syndicated copies automatically.'
            );

            return;
        }

        /*
         * Pause active sharing without removing the property
         * from the TPX Network.
         */
        if ($property->syndications()->where('status', 'active')->exists()) {
            $property->syndications()
                ->where('status', 'active')
                ->update([
                    'status' => 'paused',
                    'updated_at' => $now,
                ]);

            session()->flash(
                'success',
                'Property sharing paused. Receiving websites will place their syndicated copies On Hold automatically.'
            );

            return;
        }

        /*
         * No syndications exist yet. Remove the property from
         * the network without affecting the property listing.
         */
        $property->update([
            'exchange_available' => false,
            'exchange_withdrawn_at' => $now,
        ]);

        session()->flash(
            'success',
            'Property removed from the TPX Network.'
        );
    }

    public function deleteProperty(int $propertyId): void
    {
        $agencyId = $this->agency->id;

        $property = Property::query()
            ->where('agency_id', $agencyId)
            ->with(['media', 'syndications'])
            ->findOrFail($propertyId);

        if ($property->syndications->where('status', 'active')->isNotEmpty()) {
            session()->flash('error', 'Property Not Deleted: This property cannot be deleted because it has active syndications. Please revoke all active syndications before deleting the property.');
            return;
        }

        /*
         * Properties with syndication history must remain temporarily
         * so receiving connectors can process the removal lifecycle.
         */
        if ($property->syndications->isNotEmpty()) {
            $now = now();

            $property->syndications()->update([
                'status' => 'removed',
                'revoked_at' => $now,
                'updated_at' => $now,
            ]);

            $property->update([
                'status' => 'withdrawn',
                'exchange_available' => false,
                'exchange_withdrawn_at' => $now,
            ]);

            $this->resetPage();

            session()->flash(
                'success',
                'Property withdrawn successfully. Receiving websites will remove their syndicated copies automatically.'
            );

            return;
        }

        foreach ($property->media as $media) {
            if ($media->storage_disk && $media->storage_path) {
                Storage::disk($media->storage_disk)->delete($media->storage_path);
            }

            $media->delete();
        }

        $property->features()->detach();
        $property->labels()->detach();
        $property->sources()->delete();
        $property->delete();

        $this->resetPage();

        session()->flash('success', 'Property deleted successfully.');
    }

    public function with(): array
    {
        /*
         * SECURITY:
         * Every query on this page is restricted to the
         * authenticated user's agency.
         */
        $agencyId = $this->agency->id;

        $query = Property::query()
            ->where('agency_id', $agencyId)
            ->where('status', '!=', 'withdrawn')
            ->with([
                'primaryImage',
                'assignedUser',
            ])
            ->withCount([
                'media',
                'syndications',
                'syndications as active_syndications_count' => function ($query) {
                    $query->where('status', 'active');
                },
                'syndications as paused_syndications_count' => function ($query) {
                    $query->where('status', 'paused');
                },
            ]);

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

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->listingType === 'sale') {
            $query->where('for_sale', true);
        }

        if ($this->listingType === 'rent') {
            $query->where('for_rent', true);
        }

        if ($this->listingType === 'sale_rent') {
            $query
                ->where('for_sale', true)
                ->where('for_rent', true);
        }
if ($this->propertyType !== '') {
    $query->where('property_type', $this->propertyType);
}
if ($this->minPrice !== '' && is_numeric($this->minPrice)) {
    $minPrice = (float) $this->minPrice;

    if ($this->listingType === 'sale') {
        $query->where('sale_price', '>=', $minPrice);
    } elseif ($this->listingType === 'rent') {
        $query->where('rental_price', '>=', $minPrice);
    } elseif ($this->listingType === 'sale_rent') {
        $query
            ->where('sale_price', '>=', $minPrice)
            ->where('rental_price', '>=', $minPrice);
    } else {
        $query->where(function ($subQuery) use ($minPrice) {
            $subQuery
                ->where('sale_price', '>=', $minPrice)
                ->orWhere('rental_price', '>=', $minPrice);
        });
    }
}
if ($this->maxPrice !== '' && is_numeric($this->maxPrice)) {
    $maxPrice = (float) $this->maxPrice;

    if ($this->listingType === 'sale') {
        $query->where('sale_price', '<=', $maxPrice);
    } elseif ($this->listingType === 'rent') {
        $query->where('rental_price', '<=', $maxPrice);
    } elseif ($this->listingType === 'sale_rent') {
        $query
            ->where('sale_price', '<=', $maxPrice)
            ->where('rental_price', '<=', $maxPrice);
    } else {
        $query->where(function ($subQuery) use ($maxPrice) {
            $subQuery
                ->where('sale_price', '<=', $maxPrice)
                ->orWhere('rental_price', '<=', $maxPrice);
        });
    }
}

        if ($this->province !== '') {
            $query->where('province', $this->province);
        }

        return [
            'properties' => $query
                ->latest()
                ->paginate(20),
'propertyTypes' => Property::query()
    ->where('agency_id', $agencyId)
    ->where('status', '!=', 'withdrawn')
    ->whereNotNull('property_type')
    ->where('property_type', '!=', '')
    ->distinct()
    ->orderBy('property_type')
    ->pluck('property_type'),


            'provinces' => Property::query()
                ->where('agency_id', $agencyId)
                ->where('status', '!=', 'withdrawn')
                ->whereNotNull('province')
                ->where('province', '!=', '')
                ->distinct()
                ->orderBy('province')
                ->pluck('province'),

            'totalProperties' => Property::query()
                ->where('agency_id', $agencyId)
                ->where('status', '!=', 'withdrawn')
                ->count(),

            'activeProperties' => Property::query()
                ->where('agency_id', $agencyId)
                ->where('status', '!=', 'withdrawn')
                ->where('status', 'active')
                ->count(),

            'availableToNetworkProperties' => Property::query()
                ->where('agency_id', $agencyId)
                ->where('status', '!=', 'withdrawn')
                ->where('exchange_available', true)
                ->count(),

            'syndicatedProperties' => Property::query()
                ->where('agency_id', $agencyId)
                ->where('status', '!=', 'withdrawn')
                ->whereHas('syndications')
                ->count(),

            'activeSyndications' => \App\Models\Syndication::query()
                ->where('source_agency_id', $agencyId)
                ->where('status', 'active')
                ->count(),

            'pausedSyndications' => \App\Models\Syndication::query()
                ->where('source_agency_id', $agencyId)
                ->where('status', 'paused')
                ->count(),
        ];
    }
};
?>

<div>
    <x-agency-ui.layout title="My Properties">
<style>
.tpx-property-card { container-type: inline-size; min-width: 0; max-width: 100%; }
.tpx-property-grid { min-width: 0; }
@container (max-width: 900px) {
    .tpx-property-grid { grid-template-columns: 144px minmax(0, 1fr) 130px 130px !important; align-items: center; }
}
@container (max-width: 650px) {
    .tpx-property-grid { grid-template-columns: 144px minmax(0, 1fr) !important; }
}
@container (max-width: 420px) {
    .tpx-property-grid { grid-template-columns: minmax(0, 1fr) !important; }
}
</style>
        <div class="space-y-6">

            {{-- HEADER --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">Agency Inventory</p>
                    <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">My Properties</h3>
                    <p class="mt-2 max-w-3xl text-slate-500">
                        Manage your agency's property inventory, listing status and availability across the ThaiPropertyX network.
                    </p>
                </div>

                <a
                    href="{{ route('agency.properties.create') }}"
                    wire:navigate
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl text-sm font-semibold shadow-sm transition"
                    style="background-color:#f59e0b !important; color:#ffffff !important; padding:10px 16px !important; min-height:42px !important; height:42px !important; border:1px solid #f59e0b !important; text-decoration:none !important;"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    <span>Add Property</span>
                </a>
            </div>

            {{-- FLASH MESSAGES --}}
            @if (session('error'))
                <div class="rounded-2xl border-2 border-red-300 bg-red-50 px-5 py-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" />
                            <path stroke-linecap="round" d="M12 8v5" />
                            <path stroke-linecap="round" d="M12 16.5h.01" />
                        </svg>
                        <div>
                            <p class="font-bold text-red-800">Property Not Deleted</p>
                            <p class="mt-1 text-sm font-medium leading-6 text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="rounded-2xl border-2 border-emerald-300 bg-emerald-50 px-5 py-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9" />
                        </svg>
                        <div>
                            <p class="font-bold text-emerald-800">Success</p>
                            <p class="mt-1 text-sm font-medium leading-6 text-emerald-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- STATS --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Total Properties</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($totalProperties) }}</p>
                            <p class="mt-1 text-xs text-slate-400">Agency inventory</p>
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
                            <p class="text-sm font-semibold text-slate-500">Active Listings</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($activeProperties) }}</p>
                            <p class="mt-1 text-xs text-slate-400">Currently active</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="8" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.5 12 2.2 2.2 4.8-5" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">On TPX Network</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($availableToNetworkProperties) }}</p>
                            @if ($pausedSyndications > 0 && $activeSyndications === 0)
                                <p class="mt-1 text-xs font-semibold text-red-600">
                                    {{ $pausedSyndications }} sharing paused
                                </p>
                            @else
                                <p class="mt-1 text-xs text-slate-400">Available to partners</p>
                            @endif
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <circle cx="6" cy="12" r="2.5" />
                                <circle cx="18" cy="6" r="2.5" />
                                <circle cx="18" cy="18" r="2.5" />
                                <path stroke-linecap="round" d="m8.3 10.8 7.4-3.6M8.3 13.2l7.4 3.6" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Syndicated</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($syndicatedProperties) }}</p>
                            @if ($pausedSyndications > 0)
                                <p class="mt-1 text-xs font-semibold text-red-600">
                                    {{ $pausedSyndications }} paused
                                </p>
                            @elseif ($activeSyndications > 0)
                                <p class="mt-1 text-xs font-semibold text-emerald-600">
                                    {{ $activeSyndications }} active
                                </p>
                            @else
                                <p class="mt-1 text-xs text-slate-400">No active syndications</p>
                            @endif
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 12h8M8 17h5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            {{-- SEARCH / FILTERS --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                {{-- PRIMARY SEARCH --}}
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Search properties</label>
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path stroke-linecap="round" d="m20 20-3.5-3.5" />
                        </svg>
                        <input
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="Search by title, property ID, reference, development or location..."
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-500 focus:bg-white focus:ring-slate-500"
                        >
                    </div>
                </div>

                {{-- PRIMARY FILTER ROW --}}
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Listing Type</label>
                        <select wire:model.live="listingType" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">
                            <option value="">Sale & Rent</option>
                            <option value="sale">For Sale</option>
                            <option value="rent">For Rent</option>
                            <option value="sale_rent">For Sale & Rent</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Province</label>
                        <select wire:model.live="province" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">
                            <option value="">All provinces</option>
                            @foreach ($provinces as $provinceName)
                                <option value="{{ $provinceName }}">{{ $provinceName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                        <select wire:model.live="status" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">
                            <option value="">All statuses</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending Review</option>
                            <option value="active">Active</option>
                            <option value="sold">Sold</option>
                            <option value="rented">Rented</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                </div>

                <details class="group mt-5 border-t border-slate-200 pt-4">
                    <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-semibold text-slate-700">
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4" />
                        </svg>
                        Advanced Filters
                        <svg class="h-4 w-4 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                        </svg>
                    </summary>

                    <div class="mt-4 grid gap-4 rounded-xl bg-slate-50 p-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Property Type</label>
                            <select wire:model.live="propertyType" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">
                                <option value="">All property types</option>
                                @foreach ($propertyTypes as $type)
                                    <option value="{{ $type }}">{{ ucwords(str_replace(['_', '-'], ' ', $type)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Min Price</label>
                            <input wire:model.live.debounce.300ms="minPrice" type="number" min="0" step="1" placeholder="Minimum price" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Max Price</label>
                            <input wire:model.live.debounce.300ms="maxPrice" type="number" min="0" step="1" placeholder="Maximum price" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm">
                        </div>
                    </div>
                </details>

                @if ($search !== '' || $status !== '' || $listingType !== '' || $propertyType !== '' || $minPrice !== '' || $maxPrice !== '' || $province !== '')
                    <div class="mt-4 flex justify-end">
                        <button type="button" wire:click="resetFilters" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                            Reset Filters
                        </button>
                    </div>
                @endif
            </section>

            {{-- INVENTORY --}}
            <section>
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h4 class="text-xl font-bold text-slate-900">Property Inventory</h4>
                            <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600">{{ number_format($properties->total()) }} properties</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Properties belonging to {{ $agency->name }} matching the current filters.</p>
                    </div>
                    <p class="text-sm text-slate-400">20 properties per page</p>
                </div>

                <div class="space-y-3">
                    @forelse ($properties as $property)
                        @php
                            $statusClasses = match ($property->status) {
                                'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                'sold', 'rented' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                'withdrawn', 'expired' => 'bg-red-50 text-red-700 ring-red-200',
                                default => 'bg-slate-100 text-slate-700 ring-slate-200',
                            };
                        @endphp

                        <article class="tpx-property-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                            <div class="tpx-property-grid grid items-center gap-4" style="grid-template-columns: 144px minmax(200px, 1fr) 110px 145px 110px 120px 90px;">

                                {{-- FIXED IMAGE --}}
                                <div class="h-28 w-36 overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200"
                                     style="width:144px;height:112px;min-width:144px;max-width:144px;">
                                    @if ($property->primaryImage)
                                        <img
                                            src="{{ asset(ltrim($property->primaryImage->thumbnail_url ?: $property->primaryImage->url, '/')) }}"
                                            alt="{{ $property->primaryImage->alt_text ?: $property->title }}"
                                            style="display:block;width:144px;height:112px;min-width:144px;max-width:144px;object-fit:cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        >
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-slate-400">
                                            <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 19.5 8.5 14l3.5 3.5 2.5-2.5L21 21M6.5 8.5h.01M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- PROPERTY INFO --}}
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h5 class="truncate text-base font-bold text-slate-900">{{ $property->title }}</h5>
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                                            {{ ucwords(str_replace(['_', '-'], ' ', $property->property_type ?: 'Property')) }}
                                        </span>
                                    </div>

                                    <p class="mt-1.5 text-sm text-slate-500">
                                        @if ($property->reference)
                                            <span class="font-semibold text-slate-600">REF: {{ $property->reference }}</span>
                                            <span class="mx-1 text-slate-300">|</span>
                                        @endif
                                        {{ $property->district ?: 'Location not specified' }}@if($property->province), {{ $property->province }}@endif
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        <span class="font-semibold text-slate-600">Agent:</span>
                                        {{ $property->assignedUser?->name ?? 'Unassigned' }}
                                    </p>
                                    <div class="mt-3 flex flex-wrap items-center text-sm text-slate-600">
                                        @if (!is_null($property->bedrooms))
                                            <span class="inline-flex items-center gap-1.5" style="margin-right:24px;">
                                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 18v-6h18v6M5 12V8a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v4M12 12V9a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3"></path>
                                                </svg>
                                                {{ $property->bedrooms }} {{ $property->bedrooms == 1 ? 'Bed' : 'Beds' }}
                                            </span>
                                        @endif

                                        @if (!is_null($property->bathrooms))
                                            <span class="inline-flex items-center gap-1.5" style="margin-right:24px;"><svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-3ZM7 12V7a3 3 0 0 1 6 0"></path></svg>{{ $property->bathrooms }} {{ $property->bathrooms == 1 ? 'Bath' : 'Baths' }}</span>
                                        @endif

                                        @if ($property->for_sale)
                                            <span class="rounded-lg bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100" style="margin-right:12px;">For Sale</span>
                                        @endif
                                        @if ($property->for_rent)
                                            <span class="rounded-lg bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700 ring-1 ring-violet-100" style="margin-right:12px;">For Rent</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- STATUS / NETWORK --}}
                                <div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold ring-1 ring-inset {{ $statusClasses }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
                                        {{ ucfirst($property->status) }}
                                    </span>

                                    <div class="mt-3">
                                        @if ($property->exchange_available)
                                            <p class="text-sm font-semibold text-slate-700">TPX Network</p>

                                            @if ($property->paused_syndications_count > 0 && $property->active_syndications_count === 0)
                                                <p class="mt-1 text-xs font-bold text-red-600">Sharing paused</p>
                                            @else
                                                <p class="mt-1 text-xs text-emerald-600">Available to partners</p>
                                            @endif
                                        @else
                                            <p class="text-sm font-semibold text-slate-700">Private</p>
                                            <p class="mt-1 text-xs text-slate-400">Not on network</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- PRICE --}}
                                <div>
                                    @if ($property->for_sale && $property->sale_price !== null)
                                        <p class="text-base font-bold tabular-nums text-slate-900">
                                            {{ $property->currency }} {{ number_format((float) $property->sale_price, 0) }}
                                        </p>
                                        <p class="mt-1 text-xs font-semibold text-amber-600">Sale price</p>
                                    @endif

                                    @if ($property->for_rent && $property->rental_price !== null)
                                        <p class="{{ $property->for_sale ? 'mt-3' : '' }} text-base font-bold tabular-nums text-slate-900">
                                            {{ $property->currency }} {{ number_format((float) $property->rental_price, 0) }}
                                        </p>
                                        <p class="mt-1 text-xs font-semibold text-sky-600">
                                            Rent @if($property->rental_period) / {{ $property->rental_period }} @endif
                                        </p>
                                    @endif
                                </div>

                                {{-- SYNDICATIONS --}}
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ $property->syndications_count }} {{ Str::plural('syndication', $property->syndications_count) }}
                                    </p>

                                    @if ($property->paused_syndications_count > 0)
                                        <p class="mt-1 text-xs font-bold text-red-600">
                                            {{ $property->paused_syndications_count }} Paused
                                        </p>
                                    @elseif ($property->active_syndications_count > 0)
                                        <p class="mt-1 text-xs font-semibold text-emerald-600">
                                            {{ $property->active_syndications_count }} Active
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs text-slate-400">None active</p>
                                    @endif
                                </div>

                                {{-- NETWORK ACTION --}}
                                <div>
                                    @if (!$property->exchange_available)

                                        <button
                                            type="button"
                                            wire:click="toggleNetworkAvailability({{ $property->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleNetworkAvailability({{ $property->id }})"
                                            class="inline-flex cursor-pointer items-center rounded-lg px-3 py-2 text-sm font-bold text-amber-600 transition duration-150 hover:bg-amber-50 hover:text-amber-700 disabled:cursor-wait disabled:opacity-50"
                                        >
                                            Add to TPX Network
                                        </button>

                                    @elseif ($property->paused_syndications_count > 0)

                                        <button
                                            type="button"
                                            wire:click="toggleNetworkAvailability({{ $property->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleNetworkAvailability({{ $property->id }})"
                                            class="inline-flex cursor-pointer items-center rounded-lg px-3 py-2 text-sm font-bold text-emerald-600 transition duration-150 hover:bg-emerald-50 hover:text-emerald-700 disabled:cursor-wait disabled:opacity-50"
                                        >
                                            Resume Sharing
                                        </button>

                                    @elseif ($property->active_syndications_count > 0)

                                        <button
                                            type="button"
                                            wire:click="toggleNetworkAvailability({{ $property->id }})"
                                            wire:confirm="Pause sharing this property? Receiving websites will place their syndicated copies On Hold until sharing is resumed."
                                            wire:loading.attr="disabled"
                                            wire:target="toggleNetworkAvailability({{ $property->id }})"
                                            class="inline-flex cursor-pointer items-center rounded-lg px-3 py-2 text-sm font-bold text-amber-600 transition duration-150 hover:bg-amber-50 hover:text-amber-700 disabled:cursor-wait disabled:opacity-50"
                                        >
                                            Pause Sharing
                                        </button>

                                    @else

                                        <button
                                            type="button"
                                            wire:click="toggleNetworkAvailability({{ $property->id }})"
                                            wire:confirm="Remove this property from the TPX Network? It will no longer be available to other agencies."
                                            wire:loading.attr="disabled"
                                            wire:target="toggleNetworkAvailability({{ $property->id }})"
                                            class="inline-flex cursor-pointer items-center rounded-lg px-3 py-2 text-sm font-bold text-slate-600 transition duration-150 hover:bg-slate-100 hover:text-red-700 disabled:cursor-wait disabled:opacity-50"
                                        >
                                            Remove from TPX Network
                                        </button>

                                    @endif
                                </div>

                                {{-- ACTIONS --}}
                                <div class="xl:text-right">
                                    <div class="flex flex-col items-start xl:items-end">

                                        <a
                                            href="{{ route('agency.properties.edit', $property) }}"
                                            class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-bold text-slate-700 transition duration-150 hover:bg-amber-50 hover:text-amber-600"
                                        >
                                            Manage
                                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"></path>
                                            </svg>
                                        </a>

                                        <button
                                            type="button"
                                            wire:click="deleteProperty({{ $property->id }})"
                                            wire:confirm="Are you sure you want to permanently delete this property? This will also delete all of its images? This action cannot be undone."
                                            class="inline-flex cursor-pointer items-center rounded-lg px-3 py-2 text-sm font-bold text-red-600 transition duration-150 hover:bg-red-50 hover:text-red-800"
                                        >
                                            Delete
                                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-9 0v10m6-10v10M9 7V4h6v3m-8 0 1 13h6l1-13"></path>
                                            </svg>
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                            <p class="font-semibold text-slate-700">No properties found.</p>
                            <p class="mt-1 text-sm text-slate-500">No properties belonging to your agency match these filters.</p>
                        </div>
                    @endforelse
                </div>

                @if ($properties->hasPages())
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
                        {{ $properties->links() }}
                    </div>
                @endif
            </section>

        </div>
    </x-agency-ui.layout>
</div>
