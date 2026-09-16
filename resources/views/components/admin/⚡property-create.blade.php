<?php

use App\Models\Agency;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyMedia;
use App\Models\PropertyLabel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

new class extends Component
{
    use WithFileUploads;

    public ?int $agency_id = null;

    public string $reference = '';
    public string $title = '';
    public string $description = '';
    public string $development = '';

    public string $property_type = 'villa';

    public bool $for_sale = true;
    public bool $for_rent = false;

    public string $sale_price = '';
    public string $rental_price = '';
    public string $rental_period = 'month';
    public string $currency = 'THB';

    public string $bedrooms = '';
    public string $bathrooms = '';

    public string $furnishing = '';
    public string $year_built = '';
    public string $parking_spaces = '';

    public string $tenure = '';
    public string $lease_term_years = '';

    public string $foreign_quota = '';

    public string $common_fee = '';
    public string $common_fee_period = '';

    public string $land_size = '';
    public string $building_size = '';
    public string $size_unit = 'sqm';

    public array $selectedFeatures = [];
    public array $selectedLabels = [];

    public string $address = '';
    public string $subdistrict = '';
    public string $district = '';
    public string $province = '';
    public string $postcode = '';
    public string $country = 'Thailand';

    public string $latitude = '';
    public string $longitude = '';

    public string $status = 'draft';
    public bool $featured = false;

    /*
    |--------------------------------------------------------------------------
    | TPX Exchange
    |--------------------------------------------------------------------------
    */

    public bool $exchange_available = false;

    public array $propertyImages = [];
    public int $primaryImageIndex = 0;


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
        if (!$this->for_sale && !$this->for_rent) {
            throw ValidationException::withMessages([
                'for_sale' => 'Select at least one listing option: For Sale or For Rent.',
            ]);
        }

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

            'development' => [
                'nullable',
                'string',
                'max:255',
            ],

            'property_type' => [
                'required',
                'string',
                'max:100',
            ],

            'for_sale' => [
                'boolean',
            ],

            'for_rent' => [
                'boolean',
            ],

            'sale_price' => [
                Rule::requiredIf($this->for_sale),
                'nullable',
                'numeric',
                'min:0',
            ],

            'rental_price' => [
                Rule::requiredIf($this->for_rent),
                'nullable',
                'numeric',
                'min:0',
            ],

            'rental_period' => [
                Rule::requiredIf($this->for_rent),
                'nullable',
                Rule::in([
                    'day',
                    'week',
                    'month',
                    'year',
                ]),
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

            'furnishing' => [
                'nullable',
                Rule::in([
                    'fully_furnished',
                    'partly_furnished',
                    'unfurnished',
                ]),
            ],

            'year_built' => [
                'nullable',
                'integer',
                'min:1800',
                'max:' . (now()->year + 10),
            ],

            'parking_spaces' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            'tenure' => [
                'nullable',
                Rule::in([
                    'freehold',
                    'leasehold',
                ]),
            ],

            'lease_term_years' => [
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],

            'foreign_quota' => [
                'nullable',
                Rule::in([
                    '',
                    '1',
                    '0',
                ]),
            ],

            'common_fee' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'common_fee_period' => [
                'nullable',
                Rule::in([
                    'month',
                    'year',
                    'sqm_month',
                    'sqm_year',
                ]),
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

            'selectedFeatures' => [
                'array',
            ],

            'selectedFeatures.*' => [
                'integer',
                Rule::exists('property_features', 'id'),
            ],

            'selectedLabels' => [
                'array',
            ],

            'selectedLabels.*' => [
                'integer',
                Rule::exists('property_labels', 'id'),
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

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
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

            'exchange_available' => [
                'boolean',
            ],

            'propertyImages' => [
                'array',
                'max:50',
            ],

            'propertyImages.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:15360',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Legacy compatibility
        |--------------------------------------------------------------------------
        */

        if ($validated['for_sale']) {
            $legacyListingType = 'sale';
            $legacyPrice = $validated['sale_price'];
        } else {
            $legacyListingType = 'rent';
            $legacyPrice = $validated['rental_price'];
        }


        /*
        |--------------------------------------------------------------------------
        | Unique Property Slug
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Create Property
        |--------------------------------------------------------------------------
        */

        $property = Property::create([
            'agency_id' => $validated['agency_id'],

            'reference' => $this->nullIfBlank(
                $validated['reference'] ?? null
            ),

            'title' => $validated['title'],
            'slug' => $slug,

            'description' => $this->nullIfBlank(
                $validated['description'] ?? null
            ),

            'development' => $this->nullIfBlank(
                $validated['development'] ?? null
            ),

            'listing_type' => $legacyListingType,
            'price' => $legacyPrice,

            'for_sale' => $validated['for_sale'],
            'for_rent' => $validated['for_rent'],

            'sale_price' => $validated['for_sale']
                ? $validated['sale_price']
                : null,

            'rental_price' => $validated['for_rent']
                ? $validated['rental_price']
                : null,

            'rental_period' => $validated['for_rent']
                ? $validated['rental_period']
                : null,

            'currency' => strtoupper($validated['currency']),

            'property_type' => $validated['property_type'],

            'bedrooms' => $this->nullIfBlank(
                $validated['bedrooms'] ?? null
            ),

            'bathrooms' => $this->nullIfBlank(
                $validated['bathrooms'] ?? null
            ),

            'furnishing' => $this->nullIfBlank(
                $validated['furnishing'] ?? null
            ),

            'year_built' => $this->nullIfBlank(
                $validated['year_built'] ?? null
            ),

            'parking_spaces' => $this->nullIfBlank(
                $validated['parking_spaces'] ?? null
            ),

            'tenure' => $this->nullIfBlank(
                $validated['tenure'] ?? null
            ),

            'lease_term_years' =>
                $validated['tenure'] === 'leasehold'
                    ? $this->nullIfBlank(
                        $validated['lease_term_years'] ?? null
                    )
                    : null,

            'foreign_quota' =>
                ($validated['foreign_quota'] ?? '') === ''
                    ? null
                    : (bool) ((int) $validated['foreign_quota']),

            'common_fee' => $this->nullIfBlank(
                $validated['common_fee'] ?? null
            ),

            'common_fee_period' => $this->nullIfBlank(
                $validated['common_fee_period'] ?? null
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

            'latitude' => $this->nullIfBlank(
                $validated['latitude'] ?? null
            ),

            'longitude' => $this->nullIfBlank(
                $validated['longitude'] ?? null
            ),

            'status' => $validated['status'],
            'featured' => $validated['featured'],

            /*
            |--------------------------------------------------------------------------
            | TPX Exchange
            |--------------------------------------------------------------------------
            |
            | Once an authorised agency makes a property available to the
            | TPX Network, no additional approval is required for another
            | authorised TPX member to syndicate it.
            |
            */

            'exchange_available' => $validated['exchange_available'],

            'exchange_available_at' =>
                $validated['exchange_available']
                    ? now()
                    : null,

            'exchange_withdrawn_at' => null,

            'published_at' =>
                $validated['status'] === 'active'
                    ? now()
                    : null,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Features & Labels
        |--------------------------------------------------------------------------
        */

        $property->features()->sync(
            $validated['selectedFeatures'] ?? []
        );

        $property->labels()->sync(
            $validated['selectedLabels'] ?? []
        );


        /*
        |--------------------------------------------------------------------------
        | Property Images
        |--------------------------------------------------------------------------
        */

        if (!empty($this->propertyImages)) {
            $primaryIndex = array_key_exists(
                $this->primaryImageIndex,
                $this->propertyImages
            )
                ? $this->primaryImageIndex
                : 0;

            foreach ($this->propertyImages as $index => $image) {

                /*
                |--------------------------------------------------------------------------
                | Store Original
                |--------------------------------------------------------------------------
                */

                $path = $image->store(
                    'properties/' . $property->id,
                    'public'
                );


                /*
                |--------------------------------------------------------------------------
                | Generate Optimised Thumbnail
                |--------------------------------------------------------------------------
                */

                $thumbnailUrl = null;

                try {
                    $thumbnailDirectory =
                        'properties/' . $property->id . '/thumbnails';

                    Storage::disk('public')->makeDirectory(
                        $thumbnailDirectory
                    );

                    $thumbnailFilename =
                        pathinfo($path, PATHINFO_FILENAME) . '.jpg';

                    $thumbnailPath =
                        $thumbnailDirectory . '/' . $thumbnailFilename;

                    $manager = ImageManager::usingDriver(
                        Driver::class
                    );

                    $manager
                        ->decode(
                            Storage::disk('public')->path($path)
                        )
                        ->scaleDown(
                            width: 480,
                            height: 360
                        )
                        ->save(
                            Storage::disk('public')->path(
                                $thumbnailPath
                            ),
                            quality: 82
                        );

                    $thumbnailUrl =
                        '/storage/' . $thumbnailPath;

                } catch (\Throwable $exception) {
                    report($exception);
                }


                /*
                |--------------------------------------------------------------------------
                | Save Media Record
                |--------------------------------------------------------------------------
                */

                PropertyMedia::create([
                    'property_id' => $property->id,
                    'media_type' => 'image',
                    'url' => '/storage/' . $path,
                    'thumbnail_url' => $thumbnailUrl,
                    'title' => null,
                    'alt_text' => $property->title,
                    'sort_order' => $index,
                    'is_primary' => $index === $primaryIndex,
                ]);
            }
        }


        session()->flash(
            'success',
            'Property created successfully.'
        );

        $this->redirect(
            '/admin/properties',
            navigate: true
        );
    }


    public function reorderPropertyImages(array $orderedIndexes): void
    {
        $orderedIndexes = array_values(array_unique(
            array_map('intval', $orderedIndexes)
        ));

        $currentImages = $this->propertyImages;
        $currentPrimaryIndex = $this->primaryImageIndex;

        $reordered = [];
        $usedIndexes = [];
        $newPrimaryIndex = 0;

        foreach ($orderedIndexes as $oldIndex) {
            if (!array_key_exists($oldIndex, $currentImages)) {
                continue;
            }

            $newIndex = count($reordered);
            $reordered[] = $currentImages[$oldIndex];
            $usedIndexes[$oldIndex] = true;

            if ($currentPrimaryIndex === $oldIndex) {
                $newPrimaryIndex = $newIndex;
            }
        }

        foreach ($currentImages as $oldIndex => $image) {
            if (isset($usedIndexes[$oldIndex])) {
                continue;
            }

            $newIndex = count($reordered);
            $reordered[] = $image;

            if ($currentPrimaryIndex === (int) $oldIndex) {
                $newPrimaryIndex = $newIndex;
            }
        }

        $this->propertyImages = array_values($reordered);

        if (!empty($this->propertyImages)) {
            $this->primaryImageIndex = $newPrimaryIndex;
        } else {
            $this->primaryImageIndex = 0;
        }
    }


    public function removePropertyImage(int $index): void
    {
        if (!array_key_exists($index, $this->propertyImages)) {
            return;
        }

        unset($this->propertyImages[$index]);

        $this->propertyImages =
            array_values($this->propertyImages);

        if (empty($this->propertyImages)) {
            $this->primaryImageIndex = 0;

            return;
        }

        if ($this->primaryImageIndex === $index) {
            $this->primaryImageIndex = 0;
        } elseif ($this->primaryImageIndex > $index) {
            $this->primaryImageIndex--;
        }
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

            'featureGroups' => PropertyFeature::query()
                ->where('active', true)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->groupBy(
                    fn ($feature) =>
                        $feature->category ?: 'Other'
                ),

            'labels' => PropertyLabel::query()
                ->where('active', true)
                ->orderBy('sort_order')
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
                Create the central TPX property record for syndication,
                Houzez integration and participating agencies.
            </p>
        </div>


        <form wire:submit="save" class="space-y-6">

            {{-- PROPERTY OWNERSHIP --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h4 class="text-lg font-semibold text-slate-900">
                        Property Ownership
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Identify the agency contributing this property to TPX.
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
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >

                        @error('reference')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>
            </section>


            {{-- PROPERTY DETAILS --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h4 class="text-lg font-semibold text-slate-900">
                        Property Details
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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >

                        @error('title')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    <div class="grid gap-5 md:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Property Type
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                wire:model="property_type"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                            >
                                <option value="villa">Villa</option>
                                <option value="condominium">Condominium</option>
                                <option value="house">House</option>
                                <option value="townhouse">Townhouse</option>
                                <option value="apartment">Apartment</option>
                                <option value="land">Land</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>


                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Project / Development
                            </label>

                            <input
                                wire:model="development"
                                type="text"
                                placeholder="Example: Mali Prestige"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                            >
                        </div>

                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Description
                        </label>

                        <textarea
                            wire:model="description"
                            rows="6"
                            placeholder="Describe the property..."
                            class="w-full resize-y rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        ></textarea>
                    </div>

                </div>
            </section>


            {{-- SALE & RENT --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h4 class="text-lg font-semibold text-slate-900">
                        Sale & Rental
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        A TPX property may be offered for sale, rent, or both.
                    </p>
                </div>


                <div class="space-y-6 p-6">

                    <div class="grid gap-4 md:grid-cols-2">

                        <label class="flex cursor-pointer items-start gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <input
                                wire:model.live="for_sale"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                            >

                            <span>
                                <span class="block font-semibold text-slate-900">
                                    For Sale
                                </span>

                                <span class="mt-1 block text-sm text-slate-500">
                                    Property is available for purchase.
                                </span>
                            </span>
                        </label>


                        <label class="flex cursor-pointer items-start gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <input
                                wire:model.live="for_rent"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                            >

                            <span>
                                <span class="block font-semibold text-slate-900">
                                    For Rent
                                </span>

                                <span class="mt-1 block text-sm text-slate-500">
                                    Property is available for rental.
                                </span>
                            </span>
                        </label>

                    </div>


                    @error('for_sale')
                        <p class="text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror


                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">

                        @if ($for_sale)
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Sale Price
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    wire:model="sale_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="6500000"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                                >

                                @error('sale_price')
                                    <p class="mt-2 text-sm font-medium text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        @endif


                        @if ($for_rent)
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Rental Price
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    wire:model="rental_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="35000"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                                >

                                @error('rental_price')
                                    <p class="mt-2 text-sm font-medium text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>


                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Rental Period
                                </label>

                                <select
                                    wire:model="rental_period"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                                >
                                    <option value="day">Per Day</option>
                                    <option value="week">Per Week</option>
                                    <option value="month">Per Month</option>
                                    <option value="year">Per Year</option>
                                </select>
                            </div>
                        @endif


                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Currency
                            </label>

                            <select
                                wire:model="currency"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                            >
                                <option value="THB">THB</option>
                                <option value="USD">USD</option>
                                <option value="GBP">GBP</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>

                    </div>

                </div>
            </section>


            {{-- PROPERTY SPECIFICATIONS --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h4 class="text-lg font-semibold text-slate-900">
                        Property Specifications
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Physical and construction details.
                    </p>
                </div>


                <div class="grid gap-5 p-6 md:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Bedrooms
                        </label>

                        <input
                            wire:model="bedrooms"
                            type="number"
                            min="0"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Bathrooms
                        </label>

                        <input
                            wire:model="bathrooms"
                            type="number"
                            min="0"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Furnishing
                        </label>

                        <select
                            wire:model="furnishing"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                        >
                            <option value="">Not specified</option>
                            <option value="fully_furnished">Fully Furnished</option>
                            <option value="partly_furnished">Partly Furnished</option>
                            <option value="unfurnished">Unfurnished</option>
                        </select>
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Year Built
                        </label>

                        <input
                            wire:model="year_built"
                            type="number"
                            min="1800"
                            max="{{ now()->year + 10 }}"
                            placeholder="{{ now()->year }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Parking Spaces / Car Port
                        </label>

                        <input
                            wire:model="parking_spaces"
                            type="number"
                            min="0"
                            placeholder="2"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Number of vehicles accommodated.
                        </p>
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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Size Unit
                        </label>

                        <select
                            wire:model="size_unit"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                        >
                            <option value="sqm">
                                Square metres (sqm)
                            </option>
                        </select>
                    </div>

                </div>
            </section>


            {{-- TENURE / QUOTA / FEES --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h4 class="text-lg font-semibold text-slate-900">
                        Tenure, Quota & Fees
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Ownership structure and recurring property charges.
                    </p>
                </div>


                <div class="grid gap-5 p-6 md:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Tenure
                        </label>

                        <select
                            wire:model.live="tenure"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                        >
                            <option value="">Not specified</option>
                            <option value="freehold">Freehold</option>
                            <option value="leasehold">Leasehold</option>
                        </select>
                    </div>


                    @if ($tenure === 'leasehold')
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Lease Term
                            </label>

                            <div class="relative">
                                <input
                                    wire:model="lease_term_years"
                                    type="number"
                                    min="1"
                                    placeholder="30"
                                    class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-16 text-sm"
                                >

                                <span class="absolute right-4 top-3 text-sm text-slate-400">
                                    years
                                </span>
                            </div>
                        </div>
                    @endif


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Foreign Quota
                        </label>

                        <select
                            wire:model="foreign_quota"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                        >
                            <option value="">Not applicable / Unknown</option>
                            <option value="1">Available</option>
                            <option value="0">Not Available</option>
                        </select>
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Common / Maintenance Fee
                        </label>

                        <input
                            wire:model="common_fee"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="5000"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Fee Period
                        </label>

                        <select
                            wire:model="common_fee_period"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                        >
                            <option value="">Not specified</option>
                            <option value="month">Per Month</option>
                            <option value="year">Per Year</option>
                            <option value="sqm_month">Per sqm / Month</option>
                            <option value="sqm_year">Per sqm / Year</option>
                        </select>
                    </div>

                </div>
            </section>


            {{-- FEATURES --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h4 class="text-lg font-semibold text-slate-900">
                        Features & Amenities
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Select all features applicable to this property.
                    </p>
                </div>


                <div class="space-y-7 p-6">

                    @foreach ($featureGroups as $category => $features)

                        <div>
                            <h5 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                                {{ $category }}
                            </h5>

                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">

                                @foreach ($features as $feature)

                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-slate-300">

                                        <input
                                            wire:model="selectedFeatures"
                                            type="checkbox"
                                            value="{{ $feature->id }}"
                                            class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                                        >

                                        <span class="text-sm font-medium text-slate-700">
                                            {{ $feature->name }}
                                        </span>

                                    </label>

                                @endforeach

                            </div>
                        </div>

                    @endforeach

                </div>
            </section>


            {{-- LABELS --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h4 class="text-lg font-semibold text-slate-900">
                        Property Labels
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Marketing and location labels can be combined on a TPX listing.
                    </p>
                </div>


                <div class="grid gap-3 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    @foreach ($labels as $label)

                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <input
                                wire:model="selectedLabels"
                                type="checkbox"
                                value="{{ $label->id }}"
                                class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                            >

                            <span class="text-sm font-medium text-slate-700">
                                {{ $label->name }}
                            </span>

                        </label>

                    @endforeach

                </div>
            </section>


            {{-- LOCATION --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h4 class="text-lg font-semibold text-slate-900">
                                Location & Mapping
                            </h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Address information and geographic coordinates
                                used by TPX and Houzez.
                            </p>
                        </div>


                        <span class="inline-flex w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                            Google Maps
                        </span>

                    </div>

                </div>


                <div class="grid gap-5 p-6 md:grid-cols-2">

                    <div class="md:col-span-2">

                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>
                                <h5 class="text-sm font-semibold text-slate-900">
                                    Google Maps Location
                                </h5>

                                <p class="mt-1 text-sm text-slate-500">
                                    Enter the property address below, then find its location on Google Maps.
                                    You can drag the marker or click the map to fine-tune the position.
                                </p>
                            </div>

                            <button
                                id="tpx-find-on-google-maps"
                                type="button"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
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
                                        d="M12 21s6-5.686 6-11a6 6 0 1 0-12 0c0 5.314 6 11 6 11Z"
                                    />

                                    <circle
                                        cx="12"
                                        cy="10"
                                        r="2"
                                    />
                                </svg>

                                Find on Google Maps
                            </button>

                        </div>


                        <div
                            id="tpx-property-map"
                            wire:ignore
                            data-google-maps-key="{{ config('services.google_maps.key') }}"
                            style="width: 100%; height: 420px; min-height: 420px;"
                            class="overflow-hidden rounded-2xl border border-slate-300 bg-slate-100"
                        ></div>

                    </div>


                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Address
                        </label>

                        <input
                            wire:model="address"
                            type="text"
                            placeholder="Street address, project or development"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Country
                        </label>

                        <input
                            wire:model="country"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >
                    </div>


                    <div></div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Latitude
                        </label>

                        <input
                            wire:model="latitude"
                            type="number"
                            step="0.0000001"
                            placeholder="12.5684000"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >

                        @error('latitude')
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
                            wire:model="longitude"
                            type="number"
                            step="0.0000001"
                            placeholder="99.9577000"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        >

                        @error('longitude')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
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
                        Control the property status and availability within the TPX network.
                    </p>
                </div>


                <div class="grid gap-6 p-6 md:grid-cols-2">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Status
                        </label>

                        <select
                            wire:model="status"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
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


                    {{-- TPX NETWORK SYNDICATION --}}
                    <div class="md:col-span-2">

                        <div
                            class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5"
                        >
                            <div class="flex items-start gap-4">

                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-slate-950">
                                    <svg
                                        class="h-6 w-6"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle cx="12" cy="12" r="3" />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.5 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.5a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.5 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.14.36.35.69.6 1 .29.35.67.57 1.1.6H21a2 2 0 1 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15Z"
                                        />
                                    </svg>
                                </div>


                                <div class="min-w-0 flex-1">

                                    <h5 class="text-base font-bold text-slate-900">
                                        TPX Network Syndication
                                    </h5>

                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Make this property available to authorised
                                        agencies within the private ThaiPropertyX
                                        network.
                                    </p>


                                    <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-white p-4 shadow-sm">

                                        <input
                                            wire:model="exchange_available"
                                            type="checkbox"
                                            class="mt-1 h-5 w-5 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                                        >


                                        <span>
                                            <span class="block font-bold text-slate-900">
                                                Make available to TPX Network
                                            </span>

                                            <span class="mt-1 block text-sm leading-6 text-slate-500">
                                                By enabling this option, the owning
                                                agency authorises other active TPX
                                                member agencies to syndicate this
                                                property to their own websites.
                                                No additional approval is required.
                                            </span>
                                        </span>

                                    </label>


                                    @error('exchange_available')
                                        <p class="mt-2 text-sm font-medium text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror


                                    <div class="mt-4 rounded-xl bg-slate-900 px-4 py-3 text-sm text-slate-200">
                                        <span class="font-bold text-white">
                                            Closed network:
                                        </span>

                                        Only approved and active TPX member agencies
                                        will be able to access and syndicate properties
                                        made available to the network.
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </section>


            {{-- PROPERTY IMAGES --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h4 class="text-lg font-semibold text-slate-900">
                        Property Images
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        Select many photographs in one pass. You can review the complete batch,
                        remove unwanted images and choose the featured image before creating
                        the property.
                    </p>
                </div>


                <div class="space-y-5 p-6">

                    <label class="block cursor-pointer rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 text-center transition hover:border-amber-400 hover:bg-amber-50/40">

                        <input
                            wire:model="propertyImages"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="sr-only"
                        >


                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-900 text-white">
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
                                    d="M3 16.5V21h18v-4.5M12 3v13m0-13-4 4m4-4 4 4"
                                />
                            </svg>
                        </div>


                        <p class="mt-4 font-semibold text-slate-900">
                            Select property images
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Choose multiple JPG, PNG or WebP images at once.
                            Up to 50 images, 15 MB each.
                        </p>

                    </label>


                    <div
                        wire:loading
                        wire:target="propertyImages"
                        class="rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"
                    >
                        Uploading selected images for preview...
                        Please wait before submitting the property.
                    </div>


                    @error('propertyImages')
                        <p class="text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('propertyImages.*')
                        <p class="text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror


                    @if (count($propertyImages) > 0)

                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h5 class="font-semibold text-slate-900">
                                    Selected Images
                                </h5>

                                <p class="text-sm text-slate-500">
                                    {{ count($propertyImages) }}
                                    image{{ count($propertyImages) === 1 ? '' : 's' }}
                                    selected. Drag images into the order you want
                                    and choose one as the featured image.
                                </p>
                            </div>
                        </div>


                        <div
                            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                            x-data="{ draggedIndex: null }"
                        >

                            @foreach ($propertyImages as $index => $image)

                                <div
                                    wire:key="property-image-{{ $index }}-{{ $image->getFilename() }}"
                                    data-image-index="{{ $index }}"
                                    draggable="true"

                                    x-on:dragstart="
                                        draggedIndex =
                                            Number($el.dataset.imageIndex);

                                        $el.style.opacity = '0.55'
                                    "

                                    x-on:dragend="
                                        $el.style.opacity = '1';
                                        draggedIndex = null
                                    "

                                    x-on:dragover.prevent

                                    x-on:drop.prevent="
                                        if (draggedIndex === null) return;

                                        const targetIndex =
                                            Number($el.dataset.imageIndex);

                                        if (
                                            draggedIndex === targetIndex
                                        ) return;

                                        const cards = Array.from(
                                            $el.parentElement.querySelectorAll(
                                                '[data-image-index]'
                                            )
                                        );

                                        const indexes = cards.map(
                                            card =>
                                                Number(
                                                    card.dataset.imageIndex
                                                )
                                        );

                                        const fromPosition =
                                            indexes.indexOf(
                                                draggedIndex
                                            );

                                        const toPosition =
                                            indexes.indexOf(
                                                targetIndex
                                            );

                                        if (
                                            fromPosition === -1 ||
                                            toPosition === -1
                                        ) return;

                                        const [movedIndex] =
                                            indexes.splice(
                                                fromPosition,
                                                1
                                            );

                                        indexes.splice(
                                            toPosition,
                                            0,
                                            movedIndex
                                        );

                                        $wire.reorderPropertyImages(
                                            indexes
                                        );

                                        draggedIndex = null;
                                    "

                                    class="cursor-move overflow-hidden rounded-2xl border {{ $primaryImageIndex === $index ? 'border-amber-400 ring-2 ring-amber-200' : 'border-slate-200' }} bg-white shadow-sm transition hover:border-amber-300 hover:shadow-md"

                                    title="Drag this image to change its gallery position"
                                >

                                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">

                                        <img
                                            src="{{ $image->temporaryUrl() }}"
                                            alt="Property image preview"
                                            draggable="false"
                                            class="h-full w-full object-cover"
                                        >


                                        <div class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-lg bg-slate-950/80 px-2.5 py-1.5 text-xs font-bold text-white shadow-sm">

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
                                                    d="M8 9h8M8 15h8M5 9h.01M5 15h.01M19 9h.01M19 15h.01"
                                                />
                                            </svg>

                                            Drag to reorder
                                        </div>


                                        <div class="absolute right-3 top-3 rounded-lg bg-white/95 px-2.5 py-1.5 text-xs font-bold text-slate-700 shadow-sm">
                                            #{{ $loop->iteration }}
                                        </div>

                                    </div>


                                    <div class="space-y-3 p-3">

                                        <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">

                                            <input
                                                wire:model.live="primaryImageIndex"
                                                type="radio"
                                                name="primary_property_image"
                                                value="{{ $index }}"
                                                class="h-4 w-4 border-slate-300 text-amber-500 focus:ring-amber-500"
                                            >

                                            Featured image
                                        </label>


                                        <button
                                            type="button"
                                            wire:click="removePropertyImage({{ $index }})"
                                            class="w-full rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                                        >
                                            Remove image
                                        </button>

                                    </div>
                                </div>

                            @endforeach

                        </div>

                    @endif

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


<script>
    (() => {
        let tpxMap = null;
        let tpxMarker = null;
        let tpxGeocoder = null;


        function updateLivewireCoordinate(input, value) {
            if (!input) {
                return;
            }

            input.value = value;

            input.dispatchEvent(
                new Event('input', {
                    bubbles: true
                })
            );

            input.dispatchEvent(
                new Event('change', {
                    bubbles: true
                })
            );
        }


        function initialiseTpxPropertyMap() {

            const mapElement =
                document.getElementById(
                    'tpx-property-map'
                );

            if (!mapElement) {
                return;
            }

            if (
                mapElement.dataset.mapInitialised === '1'
            ) {
                return;
            }

            if (
                typeof window.google === 'undefined' ||
                typeof window.google.maps === 'undefined' ||
                typeof window.google.maps.Map !== 'function'
            ) {
                console.error(
                    'TPX Maps: Google Maps JavaScript API is not ready.'
                );

                return;
            }


            const latitudeInput =
                document.querySelector(
                    '[wire\\:model="latitude"]'
                );

            const longitudeInput =
                document.querySelector(
                    '[wire\\:model="longitude"]'
                );

            if (
                !latitudeInput ||
                !longitudeInput
            ) {
                console.error(
                    'TPX Maps: latitude or longitude input could not be found.'
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Initial Position
            |--------------------------------------------------------------------------
            */

            let initialPosition = {
                lat: 13.7563,
                lng: 100.5018
            };

            let initialZoom = 6;

            const existingLatitude =
                parseFloat(
                    latitudeInput.value
                );

            const existingLongitude =
                parseFloat(
                    longitudeInput.value
                );

            if (
                Number.isFinite(existingLatitude) &&
                Number.isFinite(existingLongitude)
            ) {
                initialPosition = {
                    lat: existingLatitude,
                    lng: existingLongitude
                };

                initialZoom = 16;
            }


            /*
            |--------------------------------------------------------------------------
            | Create Map
            |--------------------------------------------------------------------------
            */

            tpxMap = new google.maps.Map(
                mapElement,
                {
                    center: initialPosition,
                    zoom: initialZoom,
                    mapTypeControl: true,
                    streetViewControl: false,
                    fullscreenControl: true
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Marker
            |--------------------------------------------------------------------------
            */

            tpxMarker =
                new google.maps.Marker({
                    position: initialPosition,
                    map: tpxMap,
                    draggable: true,
                    title: 'Property location'
                });


            /*
            |--------------------------------------------------------------------------
            | Geocoder
            |--------------------------------------------------------------------------
            */

            tpxGeocoder =
                new google.maps.Geocoder();


            /*
            |--------------------------------------------------------------------------
            | Coordinate Update
            |--------------------------------------------------------------------------
            */

            function setCoordinates(position) {

                const latitude =
                    position
                        .lat()
                        .toFixed(7);

                const longitude =
                    position
                        .lng()
                        .toFixed(7);

                updateLivewireCoordinate(
                    latitudeInput,
                    latitude
                );

                updateLivewireCoordinate(
                    longitudeInput,
                    longitude
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Drag Marker
            |--------------------------------------------------------------------------
            */

            tpxMarker.addListener(
                'dragend',
                function () {

                    const position =
                        tpxMarker.getPosition();

                    if (position) {
                        setCoordinates(
                            position
                        );
                    }
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Click Map
            |--------------------------------------------------------------------------
            */

            tpxMap.addListener(
                'click',
                function (event) {

                    if (!event.latLng) {
                        return;
                    }

                    tpxMarker.setPosition(
                        event.latLng
                    );

                    setCoordinates(
                        event.latLng
                    );
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Find Address
            |--------------------------------------------------------------------------
            */

            const findButton =
                document.getElementById(
                    'tpx-find-on-google-maps'
                );

            if (findButton) {

                findButton.onclick =
                    function () {

                        const address =
                            document.querySelector(
                                '[wire\\:model="address"]'
                            )?.value || '';

                        const subdistrict =
                            document.querySelector(
                                '[wire\\:model="subdistrict"]'
                            )?.value || '';

                        const district =
                            document.querySelector(
                                '[wire\\:model="district"]'
                            )?.value || '';

                        const province =
                            document.querySelector(
                                '[wire\\:model="province"]'
                            )?.value || '';

                        const postcode =
                            document.querySelector(
                                '[wire\\:model="postcode"]'
                            )?.value || '';

                        const country =
                            document.querySelector(
                                '[wire\\:model="country"]'
                            )?.value || 'Thailand';


                        const searchAddress = [
                            address,
                            subdistrict,
                            district,
                            province,
                            postcode,
                            country
                        ]
                            .filter(
                                value =>
                                    value.trim() !== ''
                            )
                            .join(', ');


                        if (!searchAddress) {
                            alert(
                                'Enter an address or location first.'
                            );

                            return;
                        }


                        findButton.disabled = true;

                        const originalButtonText =
                            findButton.innerHTML;

                        findButton.textContent =
                            'Finding location...';


                        tpxGeocoder.geocode(
                            {
                                address:
                                    searchAddress
                            },

                            function (
                                results,
                                status
                            ) {

                                findButton.disabled =
                                    false;

                                findButton.innerHTML =
                                    originalButtonText;


                                if (
                                    status === 'OK' &&
                                    results &&
                                    results.length > 0
                                ) {

                                    const result =
                                        results[0];

                                    const location =
                                        result
                                            .geometry
                                            .location;

                                    tpxMap.setCenter(
                                        location
                                    );

                                    tpxMap.setZoom(
                                        17
                                    );

                                    tpxMarker.setPosition(
                                        location
                                    );

                                    setCoordinates(
                                        location
                                    );

                                    return;
                                }


                                alert(
                                    'Google Maps could not find this location. Google returned: ' +
                                    status
                                );
                            }
                        );
                    };
            }


            mapElement.dataset.mapInitialised =
                '1';

            console.log(
                'TPX Google Maps initialised successfully.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Load Google Maps
        |--------------------------------------------------------------------------
        */

        function loadTpxGoogleMaps() {

            const mapElement =
                document.getElementById(
                    'tpx-property-map'
                );

            if (!mapElement) {
                return;
            }


            if (
                window.google &&
                window.google.maps &&
                typeof window.google.maps.Map === 'function'
            ) {
                initialiseTpxPropertyMap();

                return;
            }


            if (
                document.getElementById(
                    'tpx-google-maps-script'
                )
            ) {
                return;
            }


            const apiKey =
                mapElement.dataset.googleMapsKey;

            if (!apiKey) {
                console.error(
                    'TPX Maps: Google Maps API key is missing from the map element.'
                );

                return;
            }


            const script =
                document.createElement(
                    'script'
                );

            script.id =
                'tpx-google-maps-script';

            script.src =
                'https://maps.googleapis.com/maps/api/js?key=' +
                encodeURIComponent(
                    apiKey
                ) +
                '&loading=async';

            script.async = true;
            script.defer = true;


            script.onload =
                function () {

                    let attempts = 0;

                    const waitForGoogleMaps =
                        window.setInterval(
                            function () {

                                attempts++;

                                if (
                                    window.google &&
                                    window.google.maps &&
                                    typeof window.google.maps.Map === 'function'
                                ) {
                                    window.clearInterval(
                                        waitForGoogleMaps
                                    );

                                    initialiseTpxPropertyMap();

                                    return;
                                }


                                if (
                                    attempts >= 50
                                ) {
                                    window.clearInterval(
                                        waitForGoogleMaps
                                    );

                                    console.error(
                                        'TPX Maps: Google Maps loaded but the Maps API did not initialise.'
                                    );
                                }

                            },
                            100
                        );
                };


            script.onerror =
                function () {
                    console.error(
                        'TPX Maps: Google Maps JavaScript failed to load.'
                    );
                };


            document.head.appendChild(
                script
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Normal Page Load
        |--------------------------------------------------------------------------
        */

        if (
            document.readyState === 'loading'
        ) {
            document.addEventListener(
                'DOMContentLoaded',
                loadTpxGoogleMaps
            );
        } else {
            loadTpxGoogleMaps();
        }


        /*
        |--------------------------------------------------------------------------
        | Livewire Navigation
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'livewire:navigated',
            function () {

                setTimeout(
                    loadTpxGoogleMaps,
                    100
                );
            }
        );

    })();
</script>