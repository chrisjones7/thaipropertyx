<?php

use App\Models\Property;
use App\Models\Syndication;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public $agency;

    public int $propertyCount = 0;
    public int $activeCount = 0;
    public int $draftCount = 0;
    public int $pausedCount = 0;
    public int $networkCount = 0;
    public int $incomingCount = 0;
    public int $sharedCount = 0;

    public int $forSaleCount = 0;
    public int $forRentCount = 0;
    public int $saleRentCount = 0;

    public array $statusCounts = [];
    public array $recentProperties = [];
    public array $recentSyndications = [];

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

        $agencyId = $this->agency->id;

        /*
         * PROPERTY STATISTICS
         */
        $properties = Property::query()
            ->where('agency_id', $agencyId);

        $this->propertyCount = (clone $properties)->count();

        $this->activeCount = (clone $properties)
            ->where('status', 'active')
            ->count();

        $this->draftCount = (clone $properties)
            ->where('status', 'draft')
            ->count();

        $this->pausedCount = (clone $properties)
            ->where('status', 'paused')
            ->count();

        /*
         * Properties actually available to the TPX Network.
         * This intentionally counts properties rather than
         * individual syndication relationships.
         */
        $this->networkCount = (clone $properties)
            ->where('exchange_available', true)
            ->count();

        /*
         * LISTING TYPE STATISTICS
         */
        $this->saleRentCount = (clone $properties)
            ->where('for_sale', true)
            ->where('for_rent', true)
            ->count();

        $this->forSaleCount = (clone $properties)
            ->where('for_sale', true)
            ->where('for_rent', false)
            ->count();

        $this->forRentCount = (clone $properties)
            ->where('for_sale', false)
            ->where('for_rent', true)
            ->count();

        /*
         * SYNDICATION STATISTICS
         */
        $this->incomingCount = Syndication::query()
            ->where('target_agency_id', $agencyId)
            ->where('status', 'active')
            ->count();

        $this->sharedCount = Syndication::query()
            ->where('source_agency_id', $agencyId)
            ->where('status', 'active')
            ->count();

        /*
         * STATUS CHART
         */
        $this->statusCounts = [
            'active' => $this->activeCount,
            'draft' => $this->draftCount,
            'paused' => $this->pausedCount,
            'other' => max(
                0,
                $this->propertyCount
                    - $this->activeCount
                    - $this->draftCount
                    - $this->pausedCount
            ),
        ];

        /*
         * RECENT PROPERTY ACTIVITY
         */
        $this->recentProperties = Property::query()
            ->where('agency_id', $agencyId)
            ->with(['primaryImage', 'images'])
            ->latest('updated_at')
            ->limit(5)
            ->get([
                'id',
                'title',
                'status',
                'sale_price',
                'rental_price',
                'currency',
                'updated_at',
            ])
            ->map(fn ($property) => [
                'id' => $property->id,
                'title' => $property->title,
                'status' => $property->status,
                'image' => $property->primaryImage?->thumbnail_url
                    ?: $property->primaryImage?->url
                    ?: $property->images->first()?->thumbnail_url
                    ?: $property->images->first()?->url,
                'updated' => $property->updated_at?->format('d M Y'),
            ])
            ->all();

        /*
         * RECENT SYNDICATION ACTIVITY
         */
        $this->recentSyndications = Syndication::query()
            ->where(function ($query) use ($agencyId) {
                $query
                    ->where('source_agency_id', $agencyId)
                    ->orWhere('target_agency_id', $agencyId);
            })
            ->with([
                'property:id,title',
                'property.primaryImage',
                'property.images',
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($syndication) => [
                'id' => $syndication->id,
                'property' => $syndication->property?->title ?? 'Property',
                'image' => $syndication->property?->primaryImage?->thumbnail_url
                    ?: $syndication->property?->primaryImage?->url
                    ?: $syndication->property?->images->first()?->thumbnail_url
                    ?: $syndication->property?->images->first()?->url,
                'direction' => (int) $syndication->target_agency_id === (int) $agencyId
                    ? 'Incoming'
                    : 'Shared',
                'status' => $syndication->status,
                'updated' => $syndication->updated_at?->format('d M Y'),
            ])
            ->all();
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/login', navigate: true);
    }
};
?>

<div>
<x-agency-ui.layout title="Dashboard">

<style>
    .tpx-dashboard {
        --tpx-border: #e2e8f0;
        --tpx-text: #0f172a;
        --tpx-muted: #64748b;
    }

    /* HERO */
    .tpx-hero {
        position: relative;
        min-height: 190px;
        margin-bottom: 22px;
        overflow: hidden;
        border: 1px solid var(--tpx-border);
        border-radius: 22px;
        background:
            linear-gradient(90deg,
                rgba(255,255,255,1) 0%,
                rgba(255,255,255,.97) 38%,
                rgba(239,246,255,.90) 65%,
                rgba(224,242,254,.85) 100%);
        box-shadow: 0 1px 3px rgba(15,23,42,.05);
    }

    .tpx-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 1;
        background:
            linear-gradient(90deg,
                rgba(255,255,255,.94) 0%,
                rgba(255,255,255,.86) 23%,
                rgba(255,255,255,.42) 42%,
                rgba(255,255,255,.08) 62%,
                rgba(255,255,255,0) 100%),
            url('/images/dashboard/hero.png?v=1');
        background-size: cover;
        background-position: center 52%;
        background-repeat: no-repeat;
    }

    .tpx-hero-content {
        position: relative;
        z-index: 2;
        max-width: 650px;
        padding: 32px 36px;
    }

    .tpx-hero-network {
        margin: 0;
        color: #d97706;
        font-size: 13px;
        font-weight: 700;
    }

    .tpx-hero-welcome {
        margin: 15px 0 0;
        color: #475569;
        font-size: 14px;
        font-weight: 600;
    }

    .tpx-hero-title {
        margin: 4px 0 0;
        color: var(--tpx-text);
        font-size: 34px;
        line-height: 1.15;
        font-weight: 800;
    }

    .tpx-hero-copy {
        margin: 12px 0 0;
        color: var(--tpx-muted);
        font-size: 14px;
    }

    /* KPI GRIDS */
    .tpx-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
    }

    .tpx-kpi-grid + .tpx-kpi-grid {
        margin-top: 14px;
    }

    .tpx-kpi {
        min-height: 126px;
        padding: 19px;
        border: 1px solid var(--tpx-border);
        border-radius: 18px;
        box-shadow: 0 1px 3px rgba(15,23,42,.05);
    }

    .tpx-kpi-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .tpx-kpi-label {
        margin: 0;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .tpx-kpi-number {
        margin: 7px 0 0;
        color: var(--tpx-text);
        font-size: 30px;
        line-height: 1;
        font-weight: 800;
    }

    .tpx-kpi-note {
        margin: 13px 0 0;
        font-size: 11px;
        font-weight: 700;
    }

    .tpx-kpi-icon {
        display: flex;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        font-size: 18px;
        font-weight: 800;
    }

    .tpx-blue   { background: #eff6ff; border-color: #dbeafe; }
    .tpx-green  { background: #ecfdf5; border-color: #d1fae5; }
    .tpx-yellow { background: #fffbeb; border-color: #fef3c7; }
    .tpx-rose   { background: #fff1f2; border-color: #ffe4e6; }
    .tpx-teal   { background: #f0fdfa; border-color: #ccfbf1; }
    .tpx-violet { background: #f5f3ff; border-color: #ede9fe; }
    .tpx-sky    { background: #f0f9ff; border-color: #e0f2fe; }
    .tpx-pink   { background: #fdf2f8; border-color: #fce7f3; }
    .tpx-orange { background: #fff7ed; border-color: #ffedd5; }
    .tpx-purple { background: #faf5ff; border-color: #f3e8ff; }

    /* ANALYTICS */
    .tpx-analytics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 22px;
    }

    .tpx-panel {
        padding: 22px;
        border: 1px solid var(--tpx-border);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(15,23,42,.05);
    }

    /* RECENT ACTIVITY */
    .tpx-recent-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin-top: 22px;
    }

    /* DASHBOARD CHARTS */
    .tpx-status-chart {
        display: flex;
        align-items: center;
        gap: 28px;
        margin-top: 24px;
    }

    .tpx-donut {
        position: relative;
        width: 160px;
        height: 160px;
        flex: 0 0 160px;
        border-radius: 50%;
    }

    .tpx-donut-centre {
        position: absolute;
        inset: 28px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #fff;
        box-shadow: inset 0 2px 5px rgba(15,23,42,.08);
    }

    .tpx-status-legend {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-size: 14px;
    }

    .tpx-legend-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        flex: 0 0 12px;
        border-radius: 50%;
        margin-right: 8px;
    }

    .tpx-dot-green  { background: #86efac; }
    .tpx-dot-blue   { background: #93c5fd; }
    .tpx-dot-orange { background: #fdba74; }
    .tpx-dot-purple { background: #d8b4fe; }

    .tpx-progress-track {
        width: 100%;
        height: 12px;
        overflow: hidden;
        border-radius: 999px;
    }

    .tpx-progress-fill {
        height: 100%;
        border-radius: 999px;
    }

    .tpx-track-pink   { background: #fce7f3; }
    .tpx-fill-pink    { background: #f9a8d4; }
    .tpx-track-sky    { background: #e0f2fe; }
    .tpx-fill-sky     { background: #7dd3fc; }
    .tpx-track-violet { background: #ede9fe; }
    .tpx-fill-violet  { background: #c4b5fd; }

    .tpx-network-chart {
        height: 208px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: 48px;
        padding: 0 20px;
        margin-top: 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .tpx-network-column {
        width: 96px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        text-align: center;
    }

    .tpx-network-bar {
        width: 64px;
        min-height: 4px;
        margin: 0 auto;
        border-radius: 12px 12px 0 0;
    }

    .tpx-network-blue {
        background: linear-gradient(to top, #60a5fa, #bae6fd);
    }

    .tpx-network-violet {
        background: linear-gradient(to top, #a78bfa, #ddd6fe);
    }

    @media (max-width: 1280px) {
        .tpx-status-chart {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    /* QUICK ACTIONS */
    .tpx-quick {
        margin-top: 22px;
        padding: 22px;
        border: 1px solid var(--tpx-border);
        border-radius: 18px;
        background: linear-gradient(90deg,#fff,#f8fafc,#eff6ff);
        box-shadow: 0 1px 3px rgba(15,23,42,.05);
    }

    .tpx-quick-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }

    .tpx-quick-buttons {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    .tpx-action {
        display: inline-flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        padding: 0 18px;
        border: 0;
        border-radius: 12px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .tpx-action-green  { background:#dcfce7; color:#166534; }
    .tpx-action-blue   { background:#dbeafe; color:#1d4ed8; }
    .tpx-action-purple { background:#ede9fe; color:#6d28d9; }
    .tpx-action-yellow { background:#fef3c7; color:#92400e; }
    .tpx-action-grey   { background:#f1f5f9; color:#475569; }

    @media (max-width: 1280px) {
        .tpx-kpi-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .tpx-kpi-grid,
        .tpx-analytics,
        .tpx-recent-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .tpx-analytics > :last-child {
            grid-column: 1 / -1;
        }

        .tpx-quick-inner {
            align-items: flex-start;
            flex-direction: column;
        }

        .tpx-quick-buttons {
            justify-content: flex-start;
        }
    }

    @media (max-width: 640px) {
        .tpx-kpi-grid,
        .tpx-analytics,
        .tpx-recent-grid {
            grid-template-columns: 1fr;
        }

        .tpx-analytics > :last-child {
            grid-column: auto;
        }

        .tpx-hero::after {
            opacity: .30;
            width: 100%;
        }

        .tpx-hero-content {
            padding: 26px 22px;
        }

        .tpx-hero-title {
            font-size: 28px;
        }

        .tpx-quick-buttons {
            width: 100%;
        }

        .tpx-action {
            width: 100%;
        }
    }
</style>

<div class="tpx-dashboard">

    @php
        $total = max($propertyCount, 1);

        $activePct = round(($activeCount / $total) * 100);
        $draftPct = round(($draftCount / $total) * 100);
        $pausedPct = round(($pausedCount / $total) * 100);
        $networkPct = round(($networkCount / $total) * 100);

        $listingTotal = max(
            $forSaleCount + $forRentCount + $saleRentCount,
            1
        );

        $salePct = round(($forSaleCount / $listingTotal) * 100);
        $rentPct = round(($forRentCount / $listingTotal) * 100);
        $bothPct = round(($saleRentCount / $listingTotal) * 100);

        $statusTotal = max(
            array_sum($statusCounts),
            1
        );

        $statusActivePct = ($statusCounts['active'] / $statusTotal) * 100;
        $statusDraftPct = ($statusCounts['draft'] / $statusTotal) * 100;
        $statusPausedPct = ($statusCounts['paused'] / $statusTotal) * 100;

        $statusDraftEnd = $statusActivePct + $statusDraftPct;
        $statusPausedEnd = $statusDraftEnd + $statusPausedPct;

        $maxNetwork = max($sharedCount, $incomingCount, 1);

        $sharedHeight = max(8, round(($sharedCount / $maxNetwork) * 100));
        $incomingHeight = max(8, round(($incomingCount / $maxNetwork) * 100));
    @endphp


    {{-- HERO --}}
    <section class="tpx-hero">

        <div class="tpx-hero-content">
            <p class="text-sm font-semibold text-amber-600">
                ThaiPropertyX Agency Network
            </p>

            <p class="mt-3 text-sm font-semibold text-slate-600">
                Welcome back, {{ auth()->user()->name }}!
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950 lg:text-4xl">
                {{ $agency->name }}
            </h1>

            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600">
                Manage your properties, syndications and network activity from one place.
            </p>
        </div>
    </section>


    {{-- KPI CARDS --}}
    <section class="tpx-kpi-grid">

        <div class="tpx-kpi tpx-blue">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600">Total Properties</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $propertyCount }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                    <span class="text-xl">⌂</span>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium text-blue-700">Agency inventory</p>
        </div>

        <div class="tpx-kpi tpx-green">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600">Active Listings</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $activeCount }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                    <span class="text-lg">✓</span>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium text-emerald-700">{{ $activePct }}% of inventory</p>
        </div>

        <div class="tpx-kpi tpx-yellow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600">Draft Listings</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $draftCount }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                    <span class="text-lg">▤</span>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium text-amber-700">{{ $draftPct }}% of inventory</p>
        </div>

        <div class="tpx-kpi tpx-rose">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600">On Hold / Paused</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $pausedCount }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                    <span class="text-lg">Ⅱ</span>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium text-rose-700">{{ $pausedPct }}% of inventory</p>
        </div>

        <div class="tpx-kpi tpx-teal">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600">Available on TPX</p>
                    <p class="mt-2 text-3xl font-bold text-slate-950">{{ $networkCount }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-teal-100 text-teal-600">
                    <span class="text-lg">↗</span>
                </div>
            </div>
            <p class="mt-3 text-xs font-medium text-teal-700">{{ $networkPct }}% of inventory</p>
        </div>

    </section>


    {{-- SECOND KPI ROW --}}
    <section class="tpx-kpi-grid">

        <div class="tpx-kpi tpx-violet">
            <p class="text-sm font-semibold text-slate-600">Incoming Syndications</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $incomingCount }}</p>
            <p class="mt-3 text-xs font-medium text-violet-700">Active from other agencies</p>
        </div>

        <div class="tpx-kpi tpx-sky">
            <p class="text-sm font-semibold text-slate-600">My Shared Syndications</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $sharedCount }}</p>
            <p class="mt-3 text-xs font-medium text-sky-700">Active receiving agencies</p>
        </div>

        <div class="tpx-kpi tpx-pink">
            <p class="text-sm font-semibold text-slate-600">For Sale</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $forSaleCount }}</p>
            <p class="mt-3 text-xs font-medium text-pink-700">{{ $salePct }}% of listed inventory</p>
        </div>

        <div class="tpx-kpi tpx-orange">
            <p class="text-sm font-semibold text-slate-600">For Rent</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $forRentCount }}</p>
            <p class="mt-3 text-xs font-medium text-orange-700">{{ $rentPct }}% of listed inventory</p>
        </div>

        <div class="tpx-kpi tpx-purple">
            <p class="text-sm font-semibold text-slate-600">Sale & Rent</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $saleRentCount }}</p>
            <p class="mt-3 text-xs font-medium text-fuchsia-700">{{ $bothPct }}% of listed inventory</p>
        </div>

    </section>


    {{-- CHARTS --}}
    <section class="tpx-analytics">

        {{-- PROPERTY STATUS --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Properties by Status</h2>
            <p class="mt-1 text-sm text-slate-500">Overview of your property inventory</p>

            <div class="tpx-status-chart">

                <div
                    class="tpx-donut"
                    style="background: conic-gradient(
                        #86efac 0 {{ $statusActivePct }}%,
                        #93c5fd {{ $statusActivePct }}% {{ $statusDraftEnd }}%,
                        #fdba74 {{ $statusDraftEnd }}% {{ $statusPausedEnd }}%,
                        #d8b4fe {{ $statusPausedEnd }}% 100%
                    );"
                >
                    <div class="tpx-donut-centre">
                        <span class="text-3xl font-bold text-slate-900">{{ $propertyCount }}</span>
                        <span class="text-xs font-medium text-slate-500">Properties</span>
                    </div>
                </div>

                <div class="tpx-status-legend">

                    <div class="flex items-center justify-between gap-5">
                        <span class="flex items-center gap-2 text-slate-600">
                            <span class="tpx-legend-dot tpx-dot-green"></span>
                            Active
                        </span>
                        <strong class="text-slate-900">{{ $statusCounts['active'] }}</strong>
                    </div>

                    <div class="flex items-center justify-between gap-5">
                        <span class="flex items-center gap-2 text-slate-600">
                            <span class="tpx-legend-dot tpx-dot-blue"></span>
                            Draft
                        </span>
                        <strong class="text-slate-900">{{ $statusCounts['draft'] }}</strong>
                    </div>

                    <div class="flex items-center justify-between gap-5">
                        <span class="flex items-center gap-2 text-slate-600">
                            <span class="tpx-legend-dot tpx-dot-orange"></span>
                            Paused
                        </span>
                        <strong class="text-slate-900">{{ $statusCounts['paused'] }}</strong>
                    </div>

                    <div class="flex items-center justify-between gap-5">
                        <span class="flex items-center gap-2 text-slate-600">
                            <span class="tpx-legend-dot tpx-dot-purple"></span>
                            Other
                        </span>
                        <strong class="text-slate-900">{{ $statusCounts['other'] }}</strong>
                    </div>

                </div>
            </div>
        </div>


        {{-- LISTING TYPE --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Listing Type</h2>
            <p class="mt-1 text-sm text-slate-500">For Sale, For Rent or Both</p>

            <div class="mt-6 space-y-5">

                <div>
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-600">For Sale</span>
                        <strong class="text-slate-900">{{ $forSaleCount }}</strong>
                    </div>
                    <div class="tpx-progress-track tpx-track-pink">
                        <div class="tpx-progress-fill tpx-fill-pink" style="width: {{ $salePct }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-600">For Rent</span>
                        <strong class="text-slate-900">{{ $forRentCount }}</strong>
                    </div>
                    <div class="tpx-progress-track tpx-track-sky">
                        <div class="tpx-progress-fill tpx-fill-sky" style="width: {{ $rentPct }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-600">Sale & Rent</span>
                        <strong class="text-slate-900">{{ $saleRentCount }}</strong>
                    </div>
                    <div class="tpx-progress-track tpx-track-violet">
                        <div class="tpx-progress-fill tpx-fill-violet" style="width: {{ $bothPct }}%"></div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Listed inventory
                    </p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ $forSaleCount + $forRentCount + $saleRentCount }}
                    </p>
                </div>

            </div>
        </div>


        {{-- TPX NETWORK --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">TPX Network</h2>
            <p class="mt-1 text-sm text-slate-500">Sharing and incoming properties</p>

            <div class="tpx-network-chart">

                <div class="tpx-network-column">
                    <span class="mb-2 text-sm font-bold text-blue-700">{{ $sharedCount }}</span>
                    <div
                        class="tpx-network-bar tpx-network-blue"
                        style="height: {{ $sharedHeight }}%;"
                    ></div>
                </div>

                <div class="tpx-network-column">
                    <span class="mb-2 text-sm font-bold text-violet-700">{{ $incomingCount }}</span>
                    <div
                        class="tpx-network-bar tpx-network-violet"
                        style="height: {{ $incomingHeight }}%;"
                    ></div>
                </div>

            </div>

            <div class="mt-3 grid grid-cols-2 text-center text-xs font-semibold text-slate-500">
                <span>My Shared</span>
                <span>Incoming</span>
            </div>
        </div>

    </section>


    {{-- RECENT ACTIVITY --}}
    <section class="tpx-recent-grid">

        {{-- RECENT PROPERTIES --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Recent Properties</h2>
                    <p class="mt-1 text-sm text-slate-500">Your latest property activity</p>
                </div>

                <a
                    href="{{ route('agency.properties') }}"
                    wire:navigate
                    class="text-sm font-semibold text-blue-600 hover:text-blue-700"
                >
                    View All →
                </a>
            </div>

            <div class="divide-y divide-slate-100">

                @forelse ($recentProperties as $property)
                    <div class="flex items-center gap-4 px-6 py-4">

                        @if(!empty($property['image']))
                            <img
                                src="{{ $property['image'] }}"
                                alt="{{ $property['title'] }}"
                                class="h-11 w-14 shrink-0 rounded-xl object-cover"
                            >
                        @else
                            <div class="flex h-11 w-14 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-100 to-indigo-100 text-lg">
                                ⌂
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">
                                {{ $property['title'] }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Updated {{ $property['updated'] }}
                            </p>
                        </div>

                        <span class="
                            rounded-full px-3 py-1 text-xs font-semibold
                            @if($property['status'] === 'active')
                                bg-green-100 text-green-700
                            @elseif($property['status'] === 'draft')
                                bg-slate-100 text-slate-600
                            @elseif($property['status'] === 'paused')
                                bg-orange-100 text-orange-700
                            @else
                                bg-purple-100 text-purple-700
                            @endif
                        ">
                            {{ ucfirst($property['status']) }}
                        </span>

                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-sm text-slate-500">
                        No property activity yet.
                    </div>
                @endforelse

            </div>
        </div>


        {{-- RECENT SYNDICATIONS --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Recent Syndications</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest TPX network activity</p>
                </div>

                <a
                    href="{{ route('agency.syndications') }}"
                    wire:navigate
                    class="text-sm font-semibold text-blue-600 hover:text-blue-700"
                >
                    View All →
                </a>
            </div>

            <div class="divide-y divide-slate-100">

                @forelse ($recentSyndications as $syndication)
                    <div class="flex items-center gap-4 px-6 py-4">

                        @if(!empty($syndication['image']))
                            <img
                                src="{{ $syndication['image'] }}"
                                alt="{{ $syndication['property'] }}"
                                class="h-11 w-14 shrink-0 rounded-xl object-cover"
                            >
                        @else
                            <div class="
                                flex h-11 w-14 shrink-0 items-center justify-center rounded-xl
                                {{ $syndication['direction'] === 'Incoming'
                                    ? 'bg-violet-100 text-violet-700'
                                    : 'bg-blue-100 text-blue-700' }}
                            ">
                                ↔
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">
                                {{ $syndication['property'] }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ $syndication['updated'] }}
                            </p>
                        </div>

                        <div class="text-right">
                            <span class="
                                inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                {{ $syndication['direction'] === 'Incoming'
                                    ? 'bg-violet-100 text-violet-700'
                                    : 'bg-blue-100 text-blue-700' }}
                            ">
                                {{ $syndication['direction'] }}
                            </span>

                            <p class="mt-1 text-[11px] font-medium text-slate-400">
                                {{ ucfirst($syndication['status']) }}
                            </p>
                        </div>

                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-sm text-slate-500">
                        No syndication activity yet.
                    </div>
                @endforelse

            </div>
        </div>

    </section>


    {{-- QUICK ACTIONS --}}
    <section class="tpx-quick">

        <div class="tpx-quick-inner">

            <div>
                <h2 class="text-lg font-bold text-slate-900">Quick Actions</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Common tasks to help you work faster
                </p>
            </div>

            <div class="tpx-quick-buttons">

                <a
                    href="{{ route('agency.properties.create') }}"
                    wire:navigate
                    class="tpx-action tpx-action-green"
                >
                    + Add Property
                </a>

                <a
                    href="{{ route('agency.property-network') }}"
                    wire:navigate
                    class="tpx-action tpx-action-blue"
                >
                    Browse TPX Network
                </a>

                <a
                    href="{{ route('agency.syndications') }}"
                    wire:navigate
                    class="tpx-action tpx-action-purple"
                >
                    My Syndications
                </a>

                <a
                    href="{{ route('agency.dashboard') }}"
                    wire:navigate
                    class="tpx-action tpx-action-grey"
                >
                    Refresh Dashboard
                </a>

            </div>
        </div>
    </section>

</div>

</x-agency-ui.layout>
</div>