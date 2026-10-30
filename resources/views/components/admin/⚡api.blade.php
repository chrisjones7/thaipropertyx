
<?php

use App\Models\Agency;
use App\Models\ApiClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $search = '';
    public string $status = 'all';

    public string $agencyId = '';
    public string $clientName = '';

    public ?string $generatedClientId = null;
    public ?string $generatedSecret = null;

    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    private function authorizeAdmin(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );
    }

    public function createClient(): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'agencyId' => [
                'required',
                Rule::exists('agencies', 'id')
                    ->where('status', 'active'),
            ],
            'clientName' => 'required|string|max:255',
        ]);

        $secret = Str::random(64);
        $clientId = 'tpx_' . Str::random(32);

        ApiClient::create([
            'agency_id' => $this->agencyId,
            'name' => $this->clientName,
            'client_id' => $clientId,
            'secret_hash' => Hash::make($secret),
            'status' => 'active',
        ]);

        $this->generatedClientId = $clientId;
        $this->generatedSecret = $secret;

        $this->reset('agencyId', 'clientName');
    }

    public function updateClientStatus(int $clientId, string $status): void
    {
        $this->authorizeAdmin();

        abort_unless(
            in_array($status, ['active', 'suspended', 'revoked'], true),
            422
        );

        $client = ApiClient::findOrFail($clientId);

        // Revocation is permanent. Create a new client if needed.
        abort_if($client->status === 'revoked', 422);

        $client->update(['status' => $status]);

        session()->flash('success', 'API client status updated.');
    }

    public function with(): array
    {
        $this->authorizeAdmin();

        $query = ApiClient::query()->with('agency');

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';

            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('client_id', 'like', $term)
                    ->orWhereHas('agency', function ($agency) use ($term) {
                        $agency->where('name', 'like', $term);
                    });
            });
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        return [
            'clients' => $query->latest()->get(),
            'agencies' => Agency::where('status', 'active')
                ->orderBy('name')
                ->get(),
            'totalClients' => ApiClient::count(),
            'activeClients' => ApiClient::where('status', 'active')->count(),
            'suspendedClients' => ApiClient::where('status', 'suspended')->count(),
            'revokedClients' => ApiClient::where('status', 'revoked')->count(),
        ];
    }
};
?>

<x-admin-ui.layout title="API & Feeds">
    <div class="mb-7">
        <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
            Integrations
        </p>

        <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
            API & Feeds
        </h3>

        <p class="mt-2 text-slate-500">
            Manage agency API credentials and TPX network connections.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($generatedSecret)
        <div class="mb-6 rounded-2xl border border-amber-300 bg-amber-50 p-5">
            <h4 class="font-bold text-slate-900">New API credentials</h4>
            <p class="mt-2 text-sm text-slate-700">
                Copy these credentials now. The secret cannot be retrieved later.
            </p>

            <div class="mt-4 space-y-3">
                <div>
                    <label class="text-sm font-semibold">Client ID</label>
                    <input readonly value="{{ $generatedClientId }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 bg-white p-3 font-mono text-sm">
                </div>

                <div>
                    <label class="text-sm font-semibold">Client Secret</label>
                    <input readonly value="{{ $generatedSecret }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 bg-white p-3 font-mono text-sm">
                </div>
            </div>

            <p class="mt-3 text-xs text-amber-900">
                Store these credentials securely. Never publish the secret.
            </p>

            <button type="button"
                wire:click="$set('generatedSecret', null)"
                class="mt-4 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
                I've saved the credentials
            </button>
        </div>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            'Total Clients' => $totalClients,
            'Active' => $activeClients,
            'Suspended' => $suspendedClients,
            'Revoked' => $revokedClients,
        ] as $label => $count)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-bold text-slate-900">Create API Client</h4>

        <form wire:submit="createClient" class="mt-5 grid gap-4 md:grid-cols-3">
            <div>
                <label class="text-sm font-semibold text-slate-700">Agency</label>
                <select wire:model="agencyId"
                    class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm">
                    <option value="">Select agency</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency->id }}">{{ $agency->name }}</option>
                    @endforeach
                </select>
                @error('agencyId')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Connection Name</label>
                <input wire:model="clientName" type="text"
                    placeholder="e.g. Houzez Website"
                    class="mt-2 w-full rounded-xl border border-slate-300 p-3 text-sm">
                @error('clientName')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-end">
                <button type="submit"
                    class="w-full rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-950 hover:bg-amber-300">
                    Generate Credentials
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-bold text-slate-900">API Connections</h4>

        <div class="mt-4 flex flex-wrap gap-3">
            <input wire:model.live.debounce.300ms="search"
                placeholder="Search agency, client or ID"
                class="rounded-xl border border-slate-300 p-3 text-sm">

            <select wire:model.live="status"
                class="rounded-xl border border-slate-300 p-3 text-sm">
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="revoked">Revoked</option>
            </select>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-slate-500">
                    <tr>
                        <th class="p-3">Agency</th>
                        <th class="p-3">Connection</th>
                        <th class="p-3">Client ID</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Last Used</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($clients as $client)
                        <tr wire:key="api-client-{{ $client->id }}"
                            class="border-b border-slate-100">
                            <td class="p-3">{{ $client->agency?->name ?? 'Unknown' }}</td>
                            <td class="p-3">{{ $client->name }}</td>
                            <td class="p-3 font-mono text-xs">{{ $client->client_id }}</td>
                            <td class="p-3">{{ ucfirst($client->status) }}</td>
                            <td class="p-3">
                                {{ $client->last_used_at?->format('d M Y H:i') ?? 'Never' }}
                            </td>
                            <td class="p-3">
                                @if ($client->status === 'active')
                                    <button type="button"
                                        wire:click="updateClientStatus({{ $client->id }}, 'suspended')"
                                        wire:confirm="Suspend this API connection?"
                                        class="font-semibold text-amber-700">
                                        Suspend
                                    </button>
                                @elseif ($client->status === 'suspended')
                                    <button type="button"
                                        wire:click="updateClientStatus({{ $client->id }}, 'active')"
                                        class="mr-3 font-semibold text-emerald-700">
                                        Activate
                                    </button>
                                @endif

                                @if ($client->status !== 'revoked')
                                    <button type="button"
                                        wire:click="updateClientStatus({{ $client->id }}, 'revoked')"
                                        wire:confirm="Permanently revoke this API client?"
                                        class="font-semibold text-red-600">
                                        Revoke
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">
                                No API clients found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-bold text-slate-900">TPX API Endpoints</h4>
        <div class="mt-4 space-y-2 font-mono text-sm text-slate-700">
            <p>GET /api/health</p>
            <p>GET /api/auth-test</p>
            <p>POST /api/v1/properties/sync</p>
            <p>GET /api/v1/exchange/properties</p>
            <p>POST /api/v1/exchange/properties/{property}/syndicate</p>
            <p>GET /api/v1/syndications</p>
        </div>
    </div>
</x-admin-ui.layout>

