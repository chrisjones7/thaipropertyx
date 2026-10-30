<?php

use App\Models\Agency;
use App\Models\Syndication;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $province = '';

    public function mount(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->agency_id,
            403
        );
    }

    public function updatedSearch(): void
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
            'province',
        ]);

        $this->resetPage();
    }

    public function with(): array
    {
        $currentAgencyId = (int) Auth::user()->agency_id;

        $query = Agency::query()
            ->where('status', 'active')
            ->where('id', '!=', $currentAgencyId)
            ->withCount([
                'outgoingSyndications as active_outgoing_syndications_count' => function ($query) {
                    $query->where('status', 'active');
                },
                'incomingSyndications as active_incoming_syndications_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->orderBy('name');

        if ($this->search !== '') {
            $search = trim($this->search);

            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('district', 'like', '%' . $search . '%')
                    ->orWhere('province', 'like', '%' . $search . '%');
            });
        }

        if ($this->province !== '') {
            $query->where('province', $this->province);
        }

        $provinces = Agency::query()
            ->where('status', 'active')
            ->whereNotNull('province')
            ->where('province', '!=', '')
            ->where('id', '!=', $currentAgencyId)
            ->distinct()
            ->orderBy('province')
            ->pluck('province');

        return [
            'agencies' => $query->paginate(12),
            'provinces' => $provinces,
        ];
    }

    public function relationshipLabel(Agency $agency): ?string
    {
        $currentAgencyId = (int) Auth::user()->agency_id;
        $agencyId = (int) $agency->id;

        $suppliesProperties = Syndication::query()
            ->where('status', 'active')
            ->where('source_agency_id', $agencyId)
            ->where('target_agency_id', $currentAgencyId)
            ->exists();

        $receivesProperties = Syndication::query()
            ->where('status', 'active')
            ->where('source_agency_id', $currentAgencyId)
            ->where('target_agency_id', $agencyId)
            ->exists();

        if ($suppliesProperties && $receivesProperties) {
            return 'You share properties with this agency';
        }

        if ($suppliesProperties) {
            return 'You share properties with this agency';
        }

        if ($receivesProperties) {
            return 'You share properties with this agency';
        }

        return null;
    }
};
?>

<div>
    <x-agency-ui.layout title="Agency Directory">

        <div class="space-y-6">

            {{-- HEADER --}}
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                    TPX Network
                </p>

                <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    Agency Directory
                </h3>

                <p class="mt-2 max-w-3xl text-slate-500">
                    Find TPX member agencies and their available contact information.
                </p>
            </div>

            {{-- FILTERS --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="grid gap-4 md:grid-cols-[1fr_240px_auto]">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Search agencies
                        </label>

                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Agency name, district or province..."
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Province
                        </label>

                        <select
                            wire:model.live="province"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-100"
                        >
                            <option value="">All provinces</option>

                            @foreach ($provinces as $item)
                                <option value="{{ $item }}">
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 md:w-auto"
                        >
                            Clear Filters
                        </button>
                    </div>

                </div>
            </div>

            {{-- DIRECTORY --}}
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">

                @forelse ($agencies as $agency)

                    @php
                        $relationship = $this->relationshipLabel($agency);
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

                        <div class="flex items-start gap-4">

                            @if ($agency->logo)
                                <img
                                    src="{{ Storage::url($agency->logo) }}"
                                    alt="{{ $agency->name }}"
                                    class="h-16 w-16 rounded-xl border border-slate-200 object-contain"
                                >
                            @else
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xl font-bold text-slate-500">
                                    {{ strtoupper(substr($agency->name, 0, 1)) }}
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    <h4 class="text-lg font-bold text-slate-900">
                                        {{ $agency->name }}
                                    </h4>

                                    @if ($agency->verified)
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">
                                            Verified
                                        </span>
                                    @endif

                                </div>

                                @if ($agency->district || $agency->province)
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $agency->district }}@if($agency->district && $agency->province), @endif{{ $agency->province }}
                                    </p>
                                @endif

                            </div>

                        </div>

                        @if ($relationship)
                            <div class="mt-5 rounded-xl bg-amber-50 px-4 py-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-amber-700">
                                    TPX Relationship
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-800">
                                    {{ $relationship }}
                                </p>
                            </div>
                        @endif

                        @if ($agency->description)
                            <p class="mt-5 text-sm leading-6 text-slate-600">
                                {{ Str::limit($agency->description, 150) }}
                            </p>
                        @endif

                        <div class="mt-5 space-y-2 border-t border-slate-100 pt-5">

                            @if ($agency->phone)
                                <a
                                    href="tel:{{ $agency->phone }}"
                                    class="flex items-center gap-3 text-sm font-semibold text-slate-700 transition hover:text-amber-600"
                                >
                                    <span class="text-slate-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.6 10.8a15.7 15.7 0 0 0 6.6 6.6l2.2-2.2a1.5 1.5 0 0 1 1.6-.35l2.7 1.1a1.5 1.5 0 0 1 .95 1.39V19a2 2 0 0 1-2 2C10.6 21 3 13.4 3 4.5a2 2 0 0 1 2-2h1.66a1.5 1.5 0 0 1 1.39.95l1.1 2.7a1.5 1.5 0 0 1-.35 1.6l-2.2 2.2Z"/></svg></span>
                                    {{ $agency->phone }}
                                </a>
                            @endif

                            @if ($agency->email)
                                <a
                                    href="mailto:{{ $agency->email }}"
                                    class="flex items-center gap-3 break-all text-sm font-semibold text-slate-700 transition hover:text-amber-600"
                                >
                                    <span class="text-slate-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" d="m3 7 9 6 9-6"/></svg></span>
                                    {{ $agency->email }}
                                </a>
                            @endif

                            @if ($agency->website)
                                <a
                                    href="{{ $agency->website }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-3 break-all text-sm font-semibold text-slate-700 transition hover:text-amber-600"
                                >
                                    <span class="text-slate-400"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg></span>
                                    {{ $agency->website }}
                                </a>
                            @endif

                        </div>

                        <div class="mt-6 border-t border-slate-100 pt-5">

                            <div class="flex items-center justify-between">

                                <div class="text-xs text-slate-400">
                                    @if ($agency->active_outgoing_syndications_count > 0)
                                        {{ $agency->active_outgoing_syndications_count }}
                                        active source
                                        {{ Str::plural('listing', $agency->active_outgoing_syndications_count) }}
                                    @elseif ($agency->active_incoming_syndications_count > 0)
                                        {{ $agency->active_incoming_syndications_count }}
                                        active received
                                        {{ Str::plural('listing', $agency->active_incoming_syndications_count) }}
                                    @else
                                        TPX Network member
                                    @endif
                                </div>

                                @if ($agency->website)
                                    <a
                                        href="{{ $agency->website }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-sm font-bold text-amber-600 transition hover:text-amber-700"
                                    >
                                        Visit Website <span aria-hidden="true">→</span>
                                    </a>
                                @endif

                            </div>

                        </div>

                    </article>

                @empty

                    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm md:col-span-2 xl:col-span-3">
                        <p class="font-semibold text-slate-700">
                            No agencies found.
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Try changing your search or province filter.
                        </p>
                    </div>

                @endforelse

            </div>

            @if ($agencies->hasPages())
                <div>
                    {{ $agencies->links() }}
                </div>
            @endif

        </div>

    </x-agency-ui.layout>
</div>

