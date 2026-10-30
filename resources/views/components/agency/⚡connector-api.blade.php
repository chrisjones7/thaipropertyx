<?php

use App\Models\ApiClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public $agency;
    public $apiClients;

    public string $connectorName = '';
    public ?string $newClientId = null;
    public ?string $newSecret = null;

    public function mount(): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless($user->isAgencyAdmin(), 403);
        abort_unless($user->agency_id, 403);

        $this->agency = $user->agency;

        abort_unless($this->agency, 403);

        $this->loadApiClients();
    }
    private function loadApiClients(): void
    {
        $this->apiClients = $this->agency
            ->apiClients()
            ->orderByDesc('created_at')
            ->get();
    }

    public function revokeConnector(int $clientId): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless($user->isAgencyAdmin(), 403);
        abort_unless($user->agency_id === $this->agency->id, 403);

        $client = ApiClient::query()
            ->where('id', $clientId)
            ->where('agency_id', $this->agency->id)
            ->firstOrFail();

        if ($client->status === 'revoked') {
            return;
        }

        $client->update([
            'status' => 'revoked',
        ]);

        $this->loadApiClients();
    }
    public function deleteConnector(int $clientId): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless($user->isAgencyAdmin(), 403);
        abort_unless($user->agency_id === $this->agency->id, 403);

        $client = ApiClient::query()
            ->where('id', $clientId)
            ->where('agency_id', $this->agency->id)
            ->firstOrFail();

        abort_unless($client->status === 'revoked', 403);

        $client->delete();

        $this->loadApiClients();
    }

    public function regenerateSecret(int $clientId): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless($user->isAgencyAdmin(), 403);
        abort_unless($user->agency_id === $this->agency->id, 403);

        $client = ApiClient::query()
            ->where('id', $clientId)
            ->where('agency_id', $this->agency->id)
            ->firstOrFail();

        abort_if($client->status === 'revoked', 403);

        $secret = Str::random(64);

        $client->update([
            'secret_hash' => Hash::make($secret),
        ]);

        $this->newClientId = $client->client_id;
        $this->newSecret = $secret;

        $this->loadApiClients();
    }
    public function toggleConnectorStatus(int $clientId): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless($user->isAgencyAdmin(), 403);
        abort_unless($user->agency_id === $this->agency->id, 403);

        $client = ApiClient::query()
            ->where('id', $clientId)
            ->where('agency_id', $this->agency->id)
            ->firstOrFail();

        if ($client->status === 'revoked') {
            return;
        }

        $client->update([
            'status' => $client->status === 'active'
                ? 'suspended'
                : 'active',
        ]);

        $this->loadApiClients();
    }
    public function createConnector(): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless($user->isAgencyAdmin(), 403);
        abort_unless($user->agency_id === $this->agency->id, 403);

        $validated = $this->validate([
            'connectorName' => ['required', 'string', 'max:100'],
        ]);

        do {
            $clientId = 'tpx-' . Str::lower(Str::random(16));
        } while (ApiClient::where('client_id', $clientId)->exists());

        $secret = Str::random(64);

        ApiClient::create([
            'agency_id' => $this->agency->id,
            'name' => $validated['connectorName'],
            'client_id' => $clientId,
            'secret_hash' => Hash::make($secret),
            'status' => 'active',
        ]);

        $this->connectorName = '';
        $this->newClientId = $clientId;
        $this->newSecret = $secret;

        $this->loadApiClients();
    }
};
?>

<x-agency-ui.layout title="Connector & API">

    <div class="space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Connector & API
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Connect your website to ThaiPropertyX and manage your API credentials.
            </p>
        </div>

        {{-- Newly generated credentials - displayed once --}}
        @if($newClientId && $newSecret)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.7 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/>
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-slate-900">
                            Save these credentials now
                        </h2>

                        <p class="mt-1 text-sm text-slate-600">
                            The secret is shown only once. Copy both values into your WordPress connector before leaving this page.
                        </p>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Client ID
                                </p>

                                <code class="mt-1 block break-all rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-slate-800">{{ $newClientId }}</code>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Secret
                                </p>

                                <code class="mt-1 block break-all rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-slate-800">{{ $newSecret }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Create Connector --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-slate-900">
                    Create Connector
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Generate credentials for a WordPress / Houzez website connected to {{ $agency->name }}.
                </p>
            </div>

            <form wire:submit="createConnector" class="px-6 py-5">
                <div class="flex flex-col gap-4 md:flex-row md:items-end">

                    <div class="flex-1">
                        <label for="connectorName" class="block text-sm font-semibold text-slate-700">
                            Connector Name
                        </label>

                        <input
                            id="connectorName"
                            type="text"
                            wire:model="connectorName"
                            placeholder="e.g. Arnold Property Website"
                            class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                        >

                        @error('connectorName')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex min-h-[42px] shrink-0 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition"
                        style="background-color:#f59e0b; border:1px solid #f59e0b;"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                        </svg>

                        Create Connector
                    </button>

                </div>
            </form>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Website Connectors
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            API clients authorised to connect to {{ $agency->name }}.
                        </p>
                    </div>

                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ $apiClients->count() }}
                        {{ $apiClients->count() === 1 ? 'Connector' : 'Connectors' }}
                    </span>
                </div>
            </div>

            @if($apiClients->isEmpty())

                <div class="px-6 py-12 text-center">
                    <p class="font-medium text-slate-900">
                        No connectors configured
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        This agency does not currently have an API connector.
                    </p>
                </div>

            @else

                <div class="divide-y divide-slate-100">

                    @foreach($apiClients as $client)

                        <div class="px-6 py-5">

                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                                <div>
                                    <div class="flex flex-wrap items-center gap-2">

                                        <h3 class="font-semibold text-slate-900">
                                            {{ $client->name }}
                                        </h3>

                                        @if($client->status === 'active')
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                Active
                                            </span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                {{ ucfirst($client->status) }}
                                            </span>
                                        @endif

                                        @if($client->status !== 'revoked')
                                        <button
                                            type="button"
                                            wire:click="revokeConnector({{ $client->id }})"
                                            wire:confirm="Are you sure you want to permanently revoke this connector? It cannot be reactivated."
                                            wire:loading.attr="disabled"
                                            wire:target="revokeConnector({{ $client->id }})"
                                            class="inline-flex items-center justify-center rounded-lg px-3 py-1 text-xs font-semibold transition disabled:opacity-50"
                                            style="background-color:#fef2f2; color:#b91c1c; border:1px solid #fecaca;"
                                        >
                                            Revoke
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="regenerateSecret({{ $client->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="regenerateSecret({{ $client->id }})"
                                            class="inline-flex items-center justify-center rounded-lg px-3 py-1 text-xs font-semibold transition disabled:opacity-50"
                                            style="background-color:#fffbeb; color:#b45309; border:1px solid #fde68a;"
                                        >
                                            Regenerate Secret
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="toggleConnectorStatus({{ $client->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleConnectorStatus({{ $client->id }})"
                                            class="inline-flex items-center justify-center rounded-lg px-3 py-1 text-xs font-semibold transition disabled:opacity-50"
                                            @if($client->status === 'active')
                                                style="background-color:#fef2f2; color:#b91c1c; border:1px solid #fecaca;"
                                            @else
                                                style="background-color:#ecfdf5; color:#047857; border:1px solid #a7f3d0;"
                                            @endif
                                        >
                                            {{ $client->status === 'active' ? 'Disable' : 'Reactivate' }}
                                        </button>
                                        @else

                                        <button
                                            type="button"
                                            wire:click="deleteConnector({{ $client->id }})"
                                            wire:confirm="Permanently delete this API connection? This cannot be undone."
                                            wire:loading.attr="disabled"
                                            wire:target="deleteConnector({{ $client->id }})"
                                            class="inline-flex items-center justify-center rounded-lg px-3 py-1 text-xs font-semibold transition disabled:opacity-50"
                                            style="background-color:#fef2f2; color:#991b1b; border:1px solid #fecaca;"
                                        >
                                            Delete API
                                        </button>

                                        @endif

                                    </div>

                                    <div class="mt-4">

                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Client ID
                                        </p>

                                        <code class="mt-1 inline-block rounded-lg bg-slate-100 px-3 py-2 text-sm text-slate-800">
                                            {{ $client->client_id }}
                                        </code>

                                    </div>
                                </div>

                                <div class="grid min-w-[260px] grid-cols-1 gap-3 text-sm sm:grid-cols-2">

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Last Used
                                        </p>

                                        <p class="mt-1 font-medium text-slate-800">
                                            {{ $client->last_used_at
                                                ? $client->last_used_at->format('d M Y H:i')
                                                : 'Never' }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Expires
                                        </p>

                                        <p class="mt-1 font-medium text-slate-800">
                                            {{ $client->expires_at
                                                ? $client->expires_at->format('d M Y')
                                                : 'No expiry' }}
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-6 py-5">

            <h2 class="font-semibold text-slate-900">
                WordPress / Houzez Connection
            </h2>

            <p class="mt-2 text-sm leading-6 text-slate-600">
                Your website connector authenticates with ThaiPropertyX using a Client ID
                and Secret. The secret is stored securely and cannot be displayed again
                after it has been generated.
            </p>

            <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <span class="font-semibold text-slate-700">
                        Client ID Header
                    </span>

                    <code class="mt-1 block text-slate-600">
                        X-TPX-Client-ID
                    </code>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <span class="font-semibold text-slate-700">
                        Secret Header
                    </span>

                    <code class="mt-1 block text-slate-600">
                        X-TPX-Secret
                    </code>
                </div>

            </div>

        </div>

    </div>

</x-agency-ui.layout>
