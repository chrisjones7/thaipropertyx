<?php

use App\Models\Agency;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public ?int $agency_id = null;

    public string $reference = '';
    public string $title = '';
    public string $description = '';

    public string $listing_type = 'sale';
    public string $property_type = 'villa';

    public string $price = '';
    public string $currency = 'THB';

    public string $bedrooms = '';
    public string $bathrooms = '';

    public string $land_size = '';
    public string $building_size = '';
    public string $size_unit = 'sqm';

    public string $address = '';
    public string $subdistrict = '';
    public string $district = '';
    public string $province = '';
    public string $postcode = '';
    public string $country = 'Thailand';

    public string $lat = '';
    public string $lng = '';

    public string $status = 'draft';
    public bool $featured = false;

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
            'agency_id' => [
                'required',
                'integer',
                Rule::exists('agencies', 'id'),
            ],

            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'listing_type' => [
                'required',
                Rule::in([
                    'sale',
                    'rent',
                ]),
            ],

            'property_type' => [
                'required',
                'string',
                'max:100',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
            ],

            'bedrooms' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            'bathrooms' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            'land_size' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'building_size' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'size_unit' => [
                'required',
                Rule::in([
                    'sqm',
                ]),
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'subdistrict' => [
                'nullable',
                'string',
                'max:150',
            ],

            'district' => [
                'nullable',
                'string',
                'max:150',
            ],

            'province' => [
                'required',
                'string',
                'max:150',
            ],

            'postcode' => [
                'nullable',
                'string',
                'max:20',
            ],

            'country' => [
                'required',
                'string',
                'max:100',
            ],

            'lat' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'lng' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'pending',
                    'active',
                    'sold',
                    'rented',
                    'withdrawn',
                    'expired',
                ]),
            ],

            'featured' => [
                'boolean',
            ],
        ]);

        $slugBase = Str::slug($validated['title']);

        if ($slugBase === '') {
            $slugBase = 'property';
        }

        $slug = $slugBase;
        $counter = 2;

        while (Property::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter;
            $counter++;
        }

        Property::create([
            'agency_id' => $validated['agency_id'],

            'reference' => $this->nullIfBlank(
                $validated['reference'] ?? null
            ),

            'title' => $validated['title'],
            'slug' => $slug,

            'description' => $this->nullIfBlank(
                $validated['description'] ?? null
            ),

            'listing_type' => $validated['listing_type'],
            'property_type' => $validated['property_type'],

            'price' => $validated['price'],
            'currency' => strtoupper($validated['currency']),

            'bedrooms' => $this->nullIfBlank(
                $validated['bedrooms'] ?? null
            ),

            'bathrooms' => $this->nullIfBlank(
                $validated['bathrooms'] ?? null
            ),

            'land_size' => $this->nullIfBlank(
                $validated['land_size'] ?? null
            ),

            'building_size' => $this->nullIfBlank(
                $validated['building_size'] ?? null
            ),

            'size_unit' => $validated['size_unit'],

            'address' => $this->nullIfBlank(
                $validated['address'] ?? null
            ),

            'subdistrict' => $this->nullIfBlank(
                $validated['subdistrict'] ?? null
            ),

            'district' => $this->nullIfBlank(
                $validated['district'] ?? null
            ),

            'province' => $validated['province'],

            'postcode' => $this->nullIfBlank(
                $validated['postcode'] ?? null
            ),

            'country' => $validated['country'],

            'lat' => $this->nullIfBlank(
                $validated['lat'] ?? null
            ),

            'lng' => $this->nullIfBlank(
                $validated['lng'] ?? null
            ),

            'status' => $validated['status'],
            'featured' => $validated['featured'],

            'published_at' =>
                $validated['status'] === 'active'
                    ? now()
                    : null,
        ]);

        session()->flash(
            'success',
            'Property created successfully.'
        );

        $this->redirect(
            '/admin/properties',
            navigate: true
        );
    }

    private function nullIfBlank(mixed $value): mixed
    {
        return $value === '' || $value === null
            ? null
            : $value;
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

<x-admin-ui.layout title="Add Property">

    <div class="mx-auto max-w-6xl">

        {{-- BACK --}}
        <div class="mb-5">

            <a
                href="{{ route('admin.properties') }}"
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

                Back to Properties
            </a>

        </div>


        {{-- HEADING --}}
        <div class="mb-7">

            <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                Exchange Inventory
            </p>

            <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                Add Property
            </h3>

            <p class="mt-2 max-w-3xl text-slate-500">
                Add a property to the central ThaiPropertyX inventory
                and assign ownership to a participating agency.
            </p>

        </div>


        <form
            wire:submit="save"
            class="space-y-6"
        >

            {{-- PROPERTY OWNERSHIP --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h4 class="text-lg font-semibold text-slate-900">
                        Property Ownership
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Identify the agency contributing this listing
                        to the TPX exchange.
                    </p>

                </div>


                <div class="grid gap-5 p-6 md:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Owning Agency
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            wire:model="agency_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
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


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Agency Reference
                        </label>

                        <input
                            wire:model="reference"
                            type="text"
                            placeholder="Example: HH-001245"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                        @error('reference')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>

            </section>


            {{-- LISTING DETAILS --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h4 class="text-lg font-semibold text-slate-900">
                        Listing Details
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Core information describing the property.
                    </p>

                </div>


                <div class="space-y-5 p-6">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Property Title
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            wire:model="title"
                            type="text"
                            placeholder="Example: Modern Pool Villa in Hua Hin"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                        @error('title')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Description
                        </label>

                        <textarea
                            wire:model="description"
                            rows="6"
                            placeholder="Describe the property..."
                            class="w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        ></textarea>

                        @error('description')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">

                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Listing Type
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                wire:model="listing_type"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                            >
                                <option value="sale">
                                    For Sale
                                </option>

                                <option value="rent">
                                    For Rent
                                </option>
                            </select>

                        </div>


                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Property Type
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                wire:model="property_type"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                            >
                                <option value="villa">
                                    Villa
                                </option>

                                <option value="condominium">
                                    Condominium
                                </option>

                                <option value="house">
                                    House
                                </option>

                                <option value="townhouse">
                                    Townhouse
                                </option>

                                <option value="apartment">
                                    Apartment
                                </option>

                                <option value="land">
                                    Land
                                </option>

                                <option value="commercial">
                                    Commercial
                                </option>
                            </select>

                        </div>


                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Bedrooms
                            </label>

                            <input
                                wire:model="bedrooms"
                                type="number"
                                min="0"
                                placeholder="3"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                            >

                            @error('bedrooms')
                                <p class="mt-2 text-sm font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Bathrooms
                            </label>

                            <input
                                wire:model="bathrooms"
                                type="number"
                                min="0"
                                placeholder="2"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                            >

                            @error('bathrooms')
                                <p class="mt-2 text-sm font-medium text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </div>

                </div>

            </section>


            {{-- PRICE & SIZE --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h4 class="text-lg font-semibold text-slate-900">
                        Price & Size
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Financial and physical property details.
                    </p>

                </div>


                <div class="grid gap-5 p-6 md:grid-cols-2 lg:grid-cols-5">

                    <div class="lg:col-span-2">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Price
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            wire:model="price"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="6500000"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                        @error('price')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Currency
                        </label>

                        <select
                            wire:model="currency"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >
                            <option value="THB">THB</option>
                            <option value="USD">USD</option>
                            <option value="GBP">GBP</option>
                            <option value="EUR">EUR</option>
                        </select>

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Land Size
                        </label>

                        <input
                            wire:model="land_size"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="500"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                        @error('land_size')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Building Size
                        </label>

                        <input
                            wire:model="building_size"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="220"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                        @error('building_size')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </div>


                <div class="px-6 pb-6">

                    <div class="max-w-xs">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Size Unit
                        </label>

                        <select
                            wire:model="size_unit"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >
                            <option value="sqm">
                                Square metres (sqm)
                            </option>
                        </select>

                    </div>

                </div>

            </section>


            {{-- LOCATION --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h4 class="text-lg font-semibold text-slate-900">
                        Location
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Property address and geographic coordinates.
                    </p>

                </div>


                <div class="grid gap-5 p-6 md:grid-cols-2">

                    <div class="md:col-span-2">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Address
                        </label>

                        <input
                            wire:model="address"
                            type="text"
                            placeholder="Street, development or project name"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Subdistrict
                        </label>

                        <input
                            wire:model="subdistrict"
                            type="text"
                            placeholder="Tambon / Khwaeng"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            District
                        </label>

                        <input
                            wire:model="district"
                            type="text"
                            placeholder="Amphoe / Khet"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Province
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            wire:model="province"
                            type="text"
                            placeholder="Example: Prachuap Khiri Khan"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                        @error('province')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Postcode
                        </label>

                        <input
                            wire:model="postcode"
                            type="text"
                            placeholder="77110"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Latitude
                        </label>

                        <input
                            wire:model="lat"
                            type="number"
                            step="0.0000001"
                            placeholder="12.5684"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                        @error('lat')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Longitude
                        </label>

                        <input
                            wire:model="lng"
                            type="number"
                            step="0.0000001"
                            placeholder="99.9577"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >

                        @error('lng')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    <div class="md:col-span-2">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Country
                        </label>

                        <input
                            wire:model="country"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:bg-white"
                        >

                    </div>

                </div>

            </section>


            {{-- PUBLISHING --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <h4 class="text-lg font-semibold text-slate-900">
                        Publishing
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Control the property's availability within the
                        TPX inventory.
                    </p>

                </div>


                <div class="grid gap-6 p-6 md:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Status
                        </label>

                        <select
                            wire:model="status"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500"
                        >
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
                            Featured Property
                        </label>

                        <label class="flex min-h-[46px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4">

                            <input
                                wire:model="featured"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                            >

                            <span class="text-sm font-medium text-slate-700">
                                Mark this property as featured
                            </span>

                        </label>

                    </div>

                </div>

            </section>


            {{-- TPX INFORMATION --}}
            <section class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm">

                <div class="flex items-start gap-4">

                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-slate-950">

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
                                d="M12 3v18M5 8h14M7 16h10"
                            />
                        </svg>

                    </div>


                    <div>

                        <h4 class="font-semibold">
                            TPX Exchange Property
                        </h4>

                        <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-300">
                            This record becomes the central TPX version of the
                            listing. Syndication permissions, participating
                            agencies and external Houzez/API sources will be
                            connected to this property record.
                        </p>

                    </div>

                </div>

            </section>


            {{-- ACTION BAR --}}
            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                <a
                    href="{{ route('admin.properties') }}"
                    wire:navigate
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-3 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg
                        wire:loading.remove
                        wire:target="save"
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

                    <svg
                        wire:loading
                        wire:target="save"
                        class="h-5 w-5 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />

                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"
                        />
                    </svg>

                    Add Property
                </button>

            </div>

        </form>

    </div>

</x-admin-ui.layout>