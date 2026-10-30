<?php

use App\Models\Property;
use App\Models\Syndication;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Property $property;
    public ?Syndication $viewSyndication = null;

    public function mount(Property $property): void
    {
        if (!Auth::check()) {
            abort(403);
        }

        if (!(
            Auth::user()->isPlatformAdmin()
            || Auth::user()->isAgencyAdmin()
            || Auth::user()->isAgent()
        )) {
            abort(403);
        }

        if (!Auth::user()->agency_id) {
            abort(403);
        }

        $agencyId = (int) Auth::user()->agency_id;

        $hasSyndicationAccess = Syndication::query()
            ->where('property_id', $property->id)
            ->where(function ($query) use ($agencyId) {
                $query
                    ->where('source_agency_id', $agencyId)
                    ->orWhere('target_agency_id', $agencyId);
            })
            ->exists();

        $isAvailableToNetwork =
            $property->status === 'active'
            && (bool) $property->exchange_available
            && $property->agency()
                ->where('sharing_enabled', true)
                ->exists();

        $authorised = $hasSyndicationAccess || $isAvailableToNetwork;

        abort_unless($authorised, 403);

        $property->load([
            'agency',
            'features',
            'labels',
            'media',
        ]);

        $this->viewSyndication = Syndication::query()
            ->where('property_id', $property->id)
            ->where(function ($query) use ($agencyId) {
                $query
                    ->where('source_agency_id', $agencyId)
                    ->orWhere('target_agency_id', $agencyId);
            })
            ->latest('updated_at')
            ->first();

        $this->property = $property;
    }
};
?>

<x-agency-ui.layout title="View Property">

    <div class="space-y-6">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

            <div>
                <a
                    href="{{ request('from') === 'network' ? route('agency.property-network') : route('agency.syndications') }}"
                    class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"
                >
                    <span aria-hidden="true">&larr;</span>
                    {{ request('from') === 'network' ? 'Back to Property Network' : 'Back to My Syndications' }}
                </a>

                <h1 class="text-2xl font-bold tracking-tight text-slate-950">
                    {{ $property->title }}
                </h1>

                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    @if ($property->reference)
                        <span>{{ $property->reference }}</span>
                    @endif

                    @if ($property->agency)
                        <span>&bull;</span>
                        <span>{{ $property->agency->name }}</span>
                    @endif
                </div>
            </div>

            <div class="inline-flex self-start items-center gap-3 rounded-xl px-4 py-3 shadow-sm" style="background-color: #fff1f2; border: 1px solid #fecdd3;">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                        <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                    </svg>
                </div>

                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-rose-700">
                        Read Only
                    </div>
                    <div class="mt-0.5 text-xs text-slate-400">
                        Shared property
                    </div>
                </div>
            </div>

        </div>


        <div class="grid gap-6 xl:grid-cols-3">

            <div class="space-y-6 xl:col-span-2">

                @if ($property->media->count())
                    @php
                        $galleryImages = $property->media
                            ->map(fn ($media) => $media->url ?? $media->path)
                            ->filter()
                            ->values();

                        $visibleImages = $galleryImages->take(9);
                        $remainingImages = max(0, $galleryImages->count() - 9);
                    @endphp

                    <section
                        x-data="{
                            active: 0,
                            images: @js($galleryImages->all()),

                            show(index) {
                                this.active = index;
                                this.$refs.galleryDialog.showModal();
                            },

                            close() {
                                this.$refs.galleryDialog.close();
                            },

                            next() {
                                this.active = (this.active + 1) % this.images.length;
                            },

                            previous() {
                                this.active = (this.active - 1 + this.images.length) % this.images.length;
                            }
                        }"
                        x-on:keydown.arrow-right.window="
                            if ($refs.galleryDialog && $refs.galleryDialog.open) next()
                        "
                        x-on:keydown.arrow-left.window="
                            if ($refs.galleryDialog && $refs.galleryDialog.open) previous()
                        "
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                    >

                        <div class="mb-4 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-950">
                                    Property Gallery
                                </h2>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $galleryImages->count() }}
                                    {{ Str::plural('image', $galleryImages->count()) }}
                                </p>
                            </div>

                            <p class="text-xs font-medium text-slate-400">
                                Click an image to enlarge
                            </p>
                        </div>


                        <div class="grid grid-cols-2 gap-3 md:grid-cols-3">

                            @foreach ($visibleImages as $index => $image)
                                <button
                                    type="button"
                                    x-on:click="show({{ $index }})"
                                    class="group relative block aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 text-left ring-1 ring-slate-200"
                                >
                                    <img
                                        src="{{ $image }}"
                                        alt="{{ $property->title }} - Image {{ $index + 1 }}"
                                        loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    >

                                    <span class="absolute inset-0 bg-black/0 transition group-hover:bg-black/10"></span>

                                    @if ($loop->last && $remainingImages > 0)
                                        <span class="absolute inset-0 flex items-center justify-center bg-slate-950/55">
                                            <span class="rounded-xl bg-slate-950/80 px-4 py-2 text-sm font-bold text-white shadow-lg">
                                                +{{ $remainingImages }} more
                                            </span>
                                        </span>
                                    @endif
                                </button>
                            @endforeach

                        </div>


                        {{-- Compact property photo viewer --}}
                        <dialog
                            x-ref="galleryDialog"
                            x-on:click="
                                if ($event.target === $refs.galleryDialog) close()
                            "
                            class="m-auto overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/70" style="width: 1050px; max-width: calc(100vw - 320px); padding: 0; position: fixed; left: calc(50% + 130px); top: 50%; transform: translate(-50%, -50%); margin: 0;"
                        >

                            <div class="flex flex-col" style="width: 100%;">

                                {{-- Header --}}
                                <div class="flex shrink-0 items-center justify-between border-b border-slate-200 bg-white px-5 py-3">

                                    <div>
                                        <h3 class="text-base font-bold text-slate-950">
                                            Photo Gallery
                                        </h3>

                                        <p class="text-xs text-slate-500">
                                            <span x-text="active + 1"></span>
                                            of
                                            <span x-text="images.length"></span>
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        x-on:click.stop="close()"
                                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-100"
                                    >
                                        <span>Close</span>
                                        <span class="text-xl leading-none">&times;</span>
                                    </button>

                                </div>


                                {{-- Image --}}
                                <div class="flex items-center justify-center bg-slate-950 p-4" style="height: 525px; min-height: 525px; max-height: 525px;">

                                    <img
                                        x-bind:src="images[active]"
                                        alt="{{ $property->title }}"
                                        class="object-contain" style="display: block; width: auto; height: auto; max-width: 100%; max-height: 493px;"
                                    >

                                </div>


                                {{-- Navigation --}}
                                <div class="flex shrink-0 items-center justify-between gap-4 border-t border-slate-200 bg-white px-5 py-4">

                                    <button
                                        type="button"
                                        x-on:click.stop="previous()"
                                        x-bind:disabled="images.length <= 1"
                                        class="inline-flex min-w-[125px] cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        <span>&larr;</span>
                                        <span>Previous</span>
                                    </button>


                                    <div class="text-center">
                                        <div class="text-sm font-bold text-slate-900">
                                            Image
                                            <span x-text="active + 1"></span>
                                            of
                                            <span x-text="images.length"></span>
                                        </div>

                                        <div class="mt-0.5 text-xs text-slate-400">
                                            Use arrows to browse
                                        </div>
                                    </div>


                                    <button
                                        type="button"
                                        x-on:click.stop="next()"
                                        x-bind:disabled="images.length <= 1"
                                        class="inline-flex min-w-[125px] cursor-pointer items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        <span>Next</span>
                                        <span>&rarr;</span>
                                    </button>

                                </div>

                            </div>

                        </dialog>

                    </section>
                @endif

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h2 class="text-lg font-bold text-slate-950">
                        Property Details
                    </h2>

                    <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Property Type</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ ucfirst($property->property_type ?? 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â') }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ ucfirst($property->status ?? 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â') }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bedrooms</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $property->bedrooms ?? 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bathrooms</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $property->bathrooms ?? 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â' }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Land Size</dt>
                            <dd class="mt-1 font-semibold text-slate-900">
                                {{ $property->land_size ? number_format($property->land_size) . ' ' . ($property->size_unit ?? 'sqm') : 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">Building Size</dt>
                            <dd class="mt-1 font-semibold text-slate-900">
                                {{ $property->building_size ? number_format($property->building_size) . ' ' . ($property->size_unit ?? 'sqm') : 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â' }}
                            </dd>
                        </div>

                    </dl>

                </section>


                @if ($property->description)
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <h2 class="text-lg font-bold text-slate-950">
                            Description
                        </h2>

                        <div class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $property->description }}</div>

                    </section>
                @endif

            </div>


            <div class="space-y-6">

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h2 class="text-lg font-bold text-slate-950">
                        Price
                    </h2>

                    <div class="mt-4 space-y-4">

                        @if ($property->for_sale)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">For Sale</p>
                                <p class="mt-1 text-2xl font-bold text-slate-950">
                                    {{ $property->currency ?? 'THB' }}
                                    {{ number_format((float) $property->sale_price) }}
                                </p>
                            </div>
                        @endif

                        @if ($property->for_rent)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">For Rent</p>
                                <p class="mt-1 text-2xl font-bold text-slate-950">
                                    {{ $property->currency ?? 'THB' }}
                                    {{ number_format((float) $property->rental_price) }}
                                </p>
                            </div>
                        @endif

                    </div>

                </section>


                @php
                    $commissionSource = $viewSyndication ?: $property->agency;
                    $commissionModel = $commissionSource?->commission_model;
                    $listingSplit = $commissionSource?->listing_agency_split;
                    $cooperatingSplit = $commissionSource?->cooperating_agency_split;
                    $saleCommission = $commissionSource?->sale_price_commission_percent;
                    $fixedCommission = $commissionSource?->fixed_commission_amount;
                    $commissionCurrency = $commissionSource?->commission_currency ?? 'THB';
                @endphp

                @if ($commissionModel)
                    <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">

                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">
                            Cooperating Commission
                        </p>

                        @if ($commissionModel === 'split')

                            <p class="mt-2 text-3xl font-bold text-emerald-700">
                                {{ rtrim(rtrim(number_format((float) $cooperatingSplit, 2), '0'), '.') }}%
                            </p>

                            <p class="mt-1 text-sm font-semibold text-emerald-800">
                                Commission Split
                            </p>

                            <div class="mt-4 border-t border-emerald-200 pt-4 text-sm text-emerald-800">
                                <div class="flex items-center justify-between gap-4">
                                    <span>Listing Agency</span>
                                    <span class="font-bold">
                                        {{ rtrim(rtrim(number_format((float) $listingSplit, 2), '0'), '.') }}%
                                    </span>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-4">
                                    <span>Cooperating Agency</span>
                                    <span class="font-bold">
                                        {{ rtrim(rtrim(number_format((float) $cooperatingSplit, 2), '0'), '.') }}%
                                    </span>
                                </div>
                            </div>

                        @elseif ($commissionModel === 'sale_percentage')

                            <p class="mt-2 text-3xl font-bold text-emerald-700">
                                {{ rtrim(rtrim(number_format((float) $saleCommission, 2), '0'), '.') }}%
                            </p>
                            <p class="mt-1 text-sm font-semibold text-emerald-800">Of Sale Price</p>

                        @elseif ($commissionModel === 'fixed_fee')

                            <p class="mt-2 text-3xl font-bold text-emerald-700">
                                {{ $commissionCurrency }} {{ number_format((float) $fixedCommission) }}
                            </p>
                            <p class="mt-1 text-sm font-semibold text-emerald-800">Fixed Commission</p>

                        @elseif ($commissionModel === 'custom')

                            <p class="mt-2 text-xl font-bold text-emerald-700">Custom Terms</p>
                            <p class="mt-1 text-sm text-emerald-800">
                                See the agency sharing terms for commission details.
                            </p>

                        @endif

                    </section>

                @endif

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h2 class="text-lg font-bold text-slate-950">
                        Location
                    </h2>

                    <div class="mt-4 space-y-1 text-sm text-slate-600">

                        @if ($property->development)
                            <p class="font-semibold text-slate-900">{{ $property->development }}</p>
                        @endif

                        @if ($property->subdistrict)
                            <p>{{ $property->subdistrict }}</p>
                        @endif

                        @if ($property->district)
                            <p>{{ $property->district }}</p>
                        @endif

                        @if ($property->province)
                            <p>{{ $property->province }}</p>
                        @endif

                        @if ($property->country)
                            <p>{{ $property->country }}</p>
                        @endif

                    </div>


                    @if ($property->latitude && $property->longitude)

                        <div
                            id="tpx-property-view-map"
                            wire:ignore
                            data-google-maps-key="{{ config('services.google_maps.key') }}"
                            data-latitude="{{ $property->latitude }}"
                            data-longitude="{{ $property->longitude }}"
                            class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
                            style="width: 100%; height: 320px; min-height: 320px;"
                        ></div>

                    @endif
                </section>


                @if ($property->features->count())
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <h2 class="text-lg font-bold text-slate-950">
                            Features
                        </h2>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($property->features as $feature)
                                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                    {{ $feature->name }}
                                </span>
                            @endforeach
                        </div>

                    </section>
                @endif

            </div>

        </div>

    </div>

</x-agency-ui.layout>

<script>
    (() => {
        function initialiseTpxPropertyViewMap() {
            const mapElement = document.getElementById('tpx-property-view-map');

            if (!mapElement || mapElement.dataset.mapInitialised === '1') {
                return;
            }

            if (
                typeof window.google === 'undefined' ||
                typeof window.google.maps === 'undefined' ||
                typeof window.google.maps.Map !== 'function'
            ) {
                return;
            }

            const latitude = parseFloat(mapElement.dataset.latitude);
            const longitude = parseFloat(mapElement.dataset.longitude);

            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                console.error('TPX Maps: invalid property coordinates.');
                return;
            }

            const position = {
                lat: latitude,
                lng: longitude
            };

            const map = new google.maps.Map(mapElement, {
                center: position,
                zoom: 16,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true
            });

            new google.maps.Marker({
                position: position,
                map: map,
                draggable: false,
                title: 'Property location'
            });

            mapElement.dataset.mapInitialised = '1';
        }

        function loadTpxPropertyViewGoogleMaps() {
            const mapElement = document.getElementById('tpx-property-view-map');

            if (!mapElement) {
                return;
            }

            if (
                window.google &&
                window.google.maps &&
                typeof window.google.maps.Map === 'function'
            ) {
                initialiseTpxPropertyViewMap();
                return;
            }

            const existingScript = document.getElementById('tpx-google-maps-script');

            if (existingScript) {
                existingScript.addEventListener(
                    'load',
                    initialiseTpxPropertyViewMap,
                    { once: true }
                );

                return;
            }

            const apiKey = mapElement.dataset.googleMapsKey;

            if (!apiKey) {
                console.error('TPX Maps: Google Maps API key is missing.');
                return;
            }

            const script = document.createElement('script');

            script.id = 'tpx-google-maps-script';
            script.src =
                'https://maps.googleapis.com/maps/api/js?key=' +
                encodeURIComponent(apiKey) +
                '&loading=async';

            script.async = true;
            script.defer = true;

            script.onload = function () {
                let attempts = 0;

                const waitForGoogleMaps = window.setInterval(function () {
                    attempts++;

                    if (
                        window.google &&
                        window.google.maps &&
                        typeof window.google.maps.Map === 'function'
                    ) {
                        window.clearInterval(waitForGoogleMaps);
                        initialiseTpxPropertyViewMap();
                        return;
                    }

                    if (attempts >= 50) {
                        window.clearInterval(waitForGoogleMaps);

                        console.error(
                            'TPX Maps: Google Maps loaded but the Maps API did not initialise.'
                        );
                    }
                }, 100);
            };

            script.onerror = function () {
                console.error('TPX Maps: Google Maps JavaScript failed to load.');
            };

            document.head.appendChild(script);
        }

        if (document.readyState === 'loading') {
            document.addEventListener(
                'DOMContentLoaded',
                loadTpxPropertyViewGoogleMaps,
                { once: true }
            );
        } else {
            loadTpxPropertyViewGoogleMaps();
        }
    })();
</script>
