<?php

use App\Models\Agency;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $listingType = '';
    public string $province = '';
    public string $agencyId = '';

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

    public function updatedProvince(): void
    {
        $this->resetPage();
    }

    public function updatedAgencyId(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'status',
            'listingType',
            'province',
            'agencyId',
        ]);

        $this->resetPage();
    }

    public function deleteProperty(int $propertyId): void
    {
        $property = Property::query()
            ->with('media')
            ->findOrFail($propertyId);

        foreach ($property->media as $media) {
            foreach ([$media->url, $media->thumbnail_url] as $url) {
                if (!$url) {
                    continue;
                }

                $path = ltrim($url, '/');

                if (str_starts_with($path, 'storage/')) {
                    $path = substr($path, strlen('storage/'));
                }

                if ($path !== '') {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $property->delete();

        $this->resetPage();
    }

    public function with(): array
    {
        $query = Property::query()
            ->with([
                'agency',
                'primaryImage',
            ])
            ->withCount([
                'media',
                'syndications',
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

        if ($this->province !== '') {
            $query->where('province', $this->province);
        }

        if ($this->agencyId !== '') {
            $query->where('agency_id', $this->agencyId);
        }

        return [
            'properties' => $query
                ->latest()
                ->paginate(20),

            'agencies' => Agency::query()
                ->orderBy('name')
                ->get(),

            'provinces' => Property::query()
                ->whereNotNull('province')
                ->where('province', '!=', '')
                ->distinct()
                ->orderBy('province')
                ->pluck('province'),

            'totalProperties' => Property::count(),

            'activeProperties' => Property::where(
                'status',
                'active'
            )->count(),

            'availableToNetworkProperties' => Property::where(
                'exchange_available',
                true
            )->count(),

            'syndicatedProperties' => Property::whereHas(
                'syndications'
            )->count(),
        ];
    }
};
?>

<x-admin-ui.layout title="Properties">

    <div class="space-y-6">

        {{-- HEADER --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                    Exchange Inventory
                </p>

                <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    Properties
                </h3>

                <p class="mt-2 max-w-3xl text-slate-500">
                    Manage the central ThaiPropertyX property inventory,
                    property ownership and syndication status.
                </p>
            </div>

            <a
                href="{{ route('admin.properties.create') }}"
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
                        d="M12 5v14m-7-7h14"
                    />
                </svg>

                Add Property
            </a>

        </div>


        {{-- STATS --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Total Properties
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ number_format($totalProperties) }}
                </p>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Active
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ number_format($activeProperties) }}
                </p>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Available to Network
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ number_format($availableToNetworkProperties) }}
                </p>
            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Syndicated
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ number_format($syndicatedProperties) }}
                </p>
            </div>

        </div>


        {{-- FILTERS --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">

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
                        Status
                    </label>

                    <select
                        wire:model.live="status"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">All statuses</option>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending Review</option>
                        <option value="active">Active</option>
                        <option value="sold">Sold</option>
                        <option value="rented">Rented</option>
                        <option value="withdrawn">Withdrawn</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Listing
                    </label>

                    <select
                        wire:model.live="listingType"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">Sale & Rent</option>
                        <option value="sale">For Sale</option>
                        <option value="rent">For Rent</option>
                        <option value="sale_rent">For Sale & Rent</option>
                    </select>
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Province
                    </label>

                    <select
                        wire:model.live="province"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">All provinces</option>

                        @foreach ($provinces as $provinceName)
                            <option value="{{ $provinceName }}">
                                {{ $provinceName }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Agency
                    </label>

                    <select
                        wire:model.live="agencyId"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                        <option value="">All agencies</option>

                        @foreach ($agencies as $agency)
                            <option value="{{ $agency->id }}">
                                {{ $agency->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>


            @if (
                $search !== '' ||
                $status !== '' ||
                $listingType !== '' ||
                $province !== '' ||
                $agencyId !== ''
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


        {{-- INVENTORY --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-5">
                <h4 class="text-lg font-semibold text-slate-900">
                    Property Inventory
                </h4>

                <p class="mt-1 text-sm text-slate-500">
                    Central inventory available within the ThaiPropertyX platform.
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
                                Agency
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Location
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Price
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Exchange
                            </th>

                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Action
                            </th>
                        </tr>
                    </thead>


                    <tbody class="divide-y divide-slate-200 bg-white">

                        @forelse ($properties as $property)

                            <tr class="transition hover:bg-slate-50">

                                {{-- PROPERTY --}}
                                <td class="px-6 py-5">

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
                                                    <svg
                                                        class="h-6 w-6"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M3 19.5 8.5 14l3.5 3.5 2.5-2.5L21 21M6.5 8.5h.01M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                                                        />
                                                    </svg>
                                                </div>

                                            @endif

                                        </div>


                                        <div>
                                            <p class="font-semibold text-slate-900">
                                                {{ $property->title }}
                                            </p>

                                            <div class="mt-1 flex flex-wrap gap-x-2 text-sm text-slate-500">

                                                <span>
                                                    {{ ucfirst($property->property_type) }}
                                                </span>

                                                @if (!is_null($property->bedrooms))
                                                    <span>
                                                        · {{ $property->bedrooms }} bed
                                                    </span>
                                                @endif

                                                @if (!is_null($property->bathrooms))
                                                    <span>
                                                        · {{ $property->bathrooms }} bath
                                                    </span>
                                                @endif

                                            </div>

                                            @if ($property->reference)
                                                <p class="mt-1 text-xs text-slate-400">
                                                    Ref: {{ $property->reference }}
                                                </p>
                                            @endif
                                        </div>

                                    </div>

                                </td>


                                {{-- AGENCY --}}
                                <td class="px-6 py-5 text-sm font-medium text-slate-700">
                                    {{ $property->agency?->name ?: '—' }}
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

                                        <p class="mt-1 text-xs font-medium text-amber-600">
                                            For Sale
                                        </p>

                                    @endif


                                    @if ($property->for_rent && $property->rental_price !== null)

                                        <p class="{{ $property->for_sale ? 'mt-2' : '' }} font-bold text-slate-900">
                                            {{ $property->currency }}
                                            {{ number_format((float) $property->rental_price, 0) }}
                                        </p>

                                        <p class="mt-1 text-xs font-medium text-sky-600">
                                            For Rent
                                            @if ($property->rental_period)
                                                / {{ $property->rental_period }}
                                            @endif
                                        </p>

                                    @endif

                                </td>


                                {{-- STATUS --}}
                                <td class="px-6 py-5">

                                    @php
                                        $statusClasses = match ($property->status) {
                                            'active' =>
                                                'bg-emerald-50 text-emerald-700 ring-emerald-200',

                                            'pending' =>
                                                'bg-amber-50 text-amber-700 ring-amber-200',

                                            'sold',
                                            'rented' =>
                                                'bg-blue-50 text-blue-700 ring-blue-200',

                                            'withdrawn',
                                            'expired' =>
                                                'bg-red-50 text-red-700 ring-red-200',

                                            default =>
                                                'bg-slate-100 text-slate-700 ring-slate-200',
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClasses }}"
                                    >
                                        {{ ucfirst($property->status) }}
                                    </span>

                                </td>


                                {{-- EXCHANGE --}}
                                <td class="px-6 py-5">

                                    @if ($property->exchange_available)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            Available to Network
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            Private
                                        </span>
                                    @endif

                                    <p class="mt-2 text-sm font-semibold text-slate-700">
                                        {{ $property->syndications_count }}
                                        {{ Str::plural('syndication', $property->syndications_count) }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $property->media_count }}
                                        {{ Str::plural('media item', $property->media_count) }}
                                    </p>

                                </td>


                                {{-- ACTION --}}
                                <td class="px-6 py-5 text-right">

                                    <div class="flex items-center justify-end gap-2">

                                        <a
                                            href="{{ route('admin.properties.edit', $property) }}"
                                            wire:navigate
                                            class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
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

                                        <button
                                            type="button"
                                            wire:click="deleteProperty({{ $property->id }})"
                                            wire:confirm="Delete this property permanently? This will also remove its uploaded images."
                                            class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-100"
                                        >
                                            Delete
                                        </button>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="7"
                                    class="px-6 py-14 text-center"
                                >
                                    <p class="font-semibold text-slate-700">
                                        No properties found.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Add a property or adjust the filters.
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