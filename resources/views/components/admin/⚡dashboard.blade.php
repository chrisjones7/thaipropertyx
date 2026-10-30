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
    public int $activeAgencyCount = 0;
    public int $pendingAgencyCount = 0;
    public int $unverifiedAgencyCount = 0;
    public int $verifiedAgencyCount = 0;
    public int $sharingAgencyCount = 0;

    public int $propertyCount = 0;
    public int $activePropertyCount = 0;
    public int $networkPropertyCount = 0;

    public int $userCount = 0;
    public int $agencyAdminCount = 0;
    public int $agentCount = 0;

    public int $syndicationCount = 0;
    public int $activeSyndicationCount = 0;

    public int $activeConnectorCount = 0;

    public function mount(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );

        $this->agencyCount = Agency::count();
        $this->activeAgencyCount = Agency::where('status', 'active')->count();
        $this->pendingAgencyCount = Agency::where('status', 'pending')->count();
        $this->unverifiedAgencyCount = Agency::where('verified', false)->count();
        $this->verifiedAgencyCount = Agency::where('verified', true)->count();
        $this->sharingAgencyCount = Agency::where('sharing_enabled', true)->count();

        $this->propertyCount = Property::count();
        $this->activePropertyCount = Property::where('status', 'active')->count();
        $this->networkPropertyCount = Property::where('exchange_available', true)->count();

        $this->userCount = User::count();
        $this->agencyAdminCount = User::where('role', 'agency_admin')->count();
        $this->agentCount = User::where('role', 'agent')->count();

        $this->syndicationCount = Syndication::count();
        $this->activeSyndicationCount = Syndication::where('status', 'active')->count();

        $this->activeConnectorCount = \App\Models\ApiClient::where('status', 'active')->count();
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

        {{-- Agencies --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Agencies</p>
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

            <div class="mt-5 pt-4 border-t border-slate-100 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">Active</span>
                    <span class="font-semibold text-slate-700">{{ $activeAgencyCount }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Pending</span>
                    <span class="font-semibold text-slate-700">{{ $pendingAgencyCount }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Unverified</span>
                    <span class="font-semibold text-slate-700">{{ $unverifiedAgencyCount }}</span>
                </div>
            </div>
        </div>

        {{-- Properties --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Properties</p>
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

            <div class="mt-5 pt-4 border-t border-slate-100 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">Active</span>
                    <span class="font-semibold text-slate-700">{{ $activePropertyCount }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Available on TPX Network</span>
                    <span class="font-semibold text-slate-700">{{ $networkPropertyCount }}</span>
                </div>
            </div>
        </div>

        {{-- Users --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Users</p>
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

            <div class="mt-5 pt-4 border-t border-slate-100 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">Agency Admins</span>
                    <span class="font-semibold text-slate-700">{{ $agencyAdminCount }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Agents</span>
                    <span class="font-semibold text-slate-700">{{ $agentCount }}</span>
                </div>
            </div>
        </div>

        {{-- Syndications --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">
                        Active Syndications
                    </p>

                    <p class="mt-2 text-4xl font-bold text-slate-900">
                        {{ $activeSyndicationCount }}
                    </p>
                </div>

                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h11l-3-3m3 3-3 3M16 17H5l3 3m-3-3 3-3"/>
                    </svg>
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">All Syndication Records</span>
                    <span class="font-semibold text-slate-700">{{ $syndicationCount }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-500">Active API Connectors</span>
                    <span class="font-semibold text-slate-700">{{ $activeConnectorCount }}</span>
                </div>
            </div>
        </div>

    </div>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-8">

        {{-- Platform Activity --}}
        <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div class="mb-6">
                <h4 class="text-lg font-semibold text-slate-900">
                    Platform Activity
                </h4>

                <p class="mt-1 text-sm text-slate-500">
                    Current ThaiPropertyX network activity
                </p>
            </div>

            <div class="grid gap-4" style="grid-template-columns: repeat(3, minmax(0, 1fr));">

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Active Agencies</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $activeAgencyCount }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Verified Agencies</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $verifiedAgencyCount }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Sharing Enabled</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $sharingAgencyCount }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Network Properties</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $networkPropertyCount }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Active Syndications</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $activeSyndicationCount }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">Active API Connectors</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $activeConnectorCount }}
                    </p>
                </div>

            </div>

        </div>

        {{-- Attention Required --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

            <div>
                <h4 class="text-lg font-semibold text-slate-900">
                    Attention Required
                </h4>

                <p class="mt-1 text-sm text-slate-500">
                    Items that may require administrator review
                </p>
            </div>

            <div class="mt-6 space-y-4">

                @if($unverifiedAgencyCount > 0)
                    <a href="{{ route('admin.agencies') }}"
                       class="block rounded-xl border border-amber-200 bg-amber-50 p-4 transition hover:bg-amber-100">

                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">
                                    Unverified Agencies
                                </p>

                                <p class="mt-1 text-sm text-slate-600">
                                    Review agencies awaiting verification.
                                </p>
                            </div>

                            <span class="inline-flex min-w-8 h-8 items-center justify-center rounded-full bg-white px-2 text-sm font-bold text-amber-700">
                                {{ $unverifiedAgencyCount }}
                            </span>
                        </div>

                    </a>
                @endif

                @if($pendingAgencyCount > 0)
                    <a href="{{ route('admin.agencies') }}"
                       class="block rounded-xl border border-blue-200 bg-blue-50 p-4 transition hover:bg-blue-100">

                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">
                                    Pending Agencies
                                </p>

                                <p class="mt-1 text-sm text-slate-600">
                                    Review pending agency accounts.
                                </p>
                            </div>

                            <span class="inline-flex min-w-8 h-8 items-center justify-center rounded-full bg-white px-2 text-sm font-bold text-blue-700">
                                {{ $pendingAgencyCount }}
                            </span>
                        </div>

                    </a>
                @endif

                @if($unverifiedAgencyCount === 0 && $pendingAgencyCount === 0)
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="font-semibold text-slate-900">
                            No agency issues
                        </p>

                        <p class="mt-1 text-sm text-slate-600">
                            There are currently no pending or unverified agencies.
                        </p>
                    </div>
                @endif

            </div>

            <div class="mt-6 pt-5 border-t border-slate-200">
                <a href="{{ route('admin.agencies') }}"
                   class="text-sm font-semibold text-slate-700 hover:text-slate-950">
                    Manage Agencies →
                </a>
            </div>

        </div>

    </div>
</x-admin-ui.layout>