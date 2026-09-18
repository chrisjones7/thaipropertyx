<?php

use App\Models\Property;
use App\Models\Syndication;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public $agency;

    public int $propertyCount = 0;
    public int $sharedCount = 0;
    public int $incomingCount = 0;

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

        $this->propertyCount = Property::where(
            'agency_id',
            $this->agency->id
        )->count();

        $this->sharedCount = Syndication::where(
            'source_agency_id',
            $this->agency->id
        )->count();

        $this->incomingCount = Syndication::where(
            'target_agency_id',
            $this->agency->id
        )->count();
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

<div><x-agency-ui.layout title="Dashboard">

    <div class="mb-8">
        <p class="text-sm font-semibold text-amber-600">
            ThaiPropertyX Agency Network
        </p>

        <h1 class="mt-2 text-3xl font-bold text-slate-900">
            {{ $agency->name }}
        </h1>

        <p class="mt-2 text-slate-500">
            Welcome back, {{ auth()->user()->name }}.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                My Properties
            </p>

            <p class="mt-2 text-4xl font-bold text-slate-900">
                {{ $propertyCount }}
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Shared to TPX
            </p>

            <p class="mt-2 text-4xl font-bold text-slate-900">
                {{ $sharedCount }}
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Incoming Syndications
            </p>

            <p class="mt-2 text-4xl font-bold text-slate-900">
                {{ $incomingCount }}
            </p>
        </div>

    </div>

</x-agency-ui.layout></div>

