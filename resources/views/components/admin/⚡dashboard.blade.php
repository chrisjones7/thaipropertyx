<?php

use App\Models\Agency;
use App\Models\Property;
use App\Models\Syndication;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public int $agencyCount = 0;
    public int $propertyCount = 0;
    public int $userCount = 0;
    public int $syndicationCount = 0;

    public function mount(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );

        $this->agencyCount = Agency::count();
        $this->propertyCount = Property::count();
        $this->userCount = User::count();
        $this->syndicationCount = Syndication::count();
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

<x-admin-ui.layout title="Dashboard">

    <div class="flex items-start justify-between gap-6 mb-8">

        <div>
            <h3 class="text-3xl font-bold text-slate-900">
                Welcome back, {{ auth()->user()->name }}
            </h3>

            <p class="mt-2 text-slate-500">
                Here is the current overview of the ThaiPropertyX exchange.
            </p>
        </div>

        <button
            class="rounded-xl bg-slate-950 text-white px-5 py-3 font-semibold hover:bg-slate-800 transition"
        >
            + Add Property
        </button>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Agencies
                    </p>

                    <p class="mt-2 text-4xl font-bold text-slate-900">
                        {{ $agencyCount }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                    </svg>
                </div>

            </div>

            <p class="mt-5 text-xs text-slate-400">
                Participating property agencies
            </p>

        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Properties
                    </p>

                    <p class="mt-2 text-4xl font-bold text-slate-900">
                        {{ $propertyCount }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z"/>
                    </svg>
                </div>

            </div>

            <p class="mt-5 text-xs text-slate-400">
                Listings currently in the exchange
            </p>

        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Users
                    </p>

                    <p class="mt-2 text-4xl font-bold text-slate-900">
                        {{ $userCount }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>

            </div>

            <p class="mt-5 text-xs text-slate-400">
                Platform administrators and agents
            </p>

        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Syndications
                    </p>

                    <p class="mt-2 text-4xl font-bold text-slate-900">
                        {{ $syndicationCount }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h11l-3-3m3 3-3 3M16 17H5l3 3m-3-3 3-3"/>
                    </svg>
                </div>

            </div>

            <p class="mt-5 text-xs text-slate-400">
                Active listing exchange relationships
            </p>

        </div>

    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-8">

        <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="flex items-center justify-between mb-6">

                <div>
                    <h4 class="text-lg font-semibold text-slate-900">
                        Exchange Overview
                    </h4>

                    <p class="text-sm text-slate-500 mt-1">
                        Current platform status
                    </p>
                </div>

                <span class="inline-flex items-center gap-2 text-sm font-medium text-emerald-700 bg-emerald-50 rounded-full px-3 py-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Operational
                </span>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div class="rounded-xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Database
                    </p>

                    <p class="mt-2 font-semibold text-slate-900">
                        MySQL Connected
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Authentication
                    </p>

                    <p class="mt-2 font-semibold text-slate-900">
                        Active
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Property Exchange
                    </p>

                    <p class="mt-2 font-semibold text-slate-900">
                        Ready
                    </p>
                </div>

            </div>

        </div>

        <div class="bg-slate-950 text-white rounded-2xl p-6 shadow-sm">

            <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">
                ThaiPropertyX
            </p>

            <h4 class="mt-3 text-xl font-bold">
                Property Exchange Platform
            </h4>

            <p class="mt-3 text-sm leading-6 text-slate-300">
                Connect agencies, share listings and build a central
                property inventory across Thailand.
            </p>

            <div class="mt-6 pt-5 border-t border-slate-800">

                <p class="text-xs text-slate-500">
                    Platform status
                </p>

                <p class="mt-1 text-sm font-semibold">
                    Core infrastructure operational
                </p>

            </div>

        </div>

    </div>

</x-admin-ui.layout>