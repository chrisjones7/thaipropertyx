<?php

use App\Models\MembershipEnquiry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $search = '';
    public string $filter = 'all';
    public ?int $selectedId = null;
    public string $applicationStatus = 'new';
    public string $adminNotes = '';
    public bool $saved = false;

    public function mount(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );
    }

    public function openEnquiry(int $id): void
    {
        abort_unless(Auth::check() && Auth::user()->isPlatformAdmin(), 403);

        $enquiry = MembershipEnquiry::findOrFail($id);

        if ($enquiry->read_at === null) {
            $enquiry->update(['read_at' => now()]);
        }

        $this->selectedId = $enquiry->id;
        $this->applicationStatus = $enquiry->status;
        $this->adminNotes = $enquiry->admin_notes ?? '';
        $this->saved = false;
    }

    public function closeEnquiry(): void
    {
        $this->selectedId = null;
        $this->saved = false;
    }

    public function saveEnquiry(): void
    {
        abort_unless(Auth::check() && Auth::user()->isPlatformAdmin(), 403);

        $this->validate([
            'applicationStatus' => ['required', Rule::in([
                'new', 'contacted', 'approved', 'declined'
            ])],
            'adminNotes' => ['nullable', 'string', 'max:10000'],
        ]);

        $enquiry = MembershipEnquiry::findOrFail($this->selectedId);

        $enquiry->update([
            'status' => $this->applicationStatus,
            'admin_notes' => $this->adminNotes,
            'reviewed_at' => now(),
        ]);

        $this->saved = true;
    }

    public function with(): array
    {
        $query = MembershipEnquiry::query()->latest();

        if ($this->search !== '') {
            $search = '%' . $this->search . '%';

            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', $search)
                  ->orWhere('contact_name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('location', 'like', $search);
            });
        }

        if ($this->filter === 'unread') {
            $query->unread();
        } elseif (in_array($this->filter, [
            'new', 'contacted', 'approved', 'declined'
        ], true)) {
            $query->where('status', $this->filter);
        }

        return [
            'enquiries' => $query->paginate(15),
            'unreadCount' => MembershipEnquiry::unread()->count(),
            'totalCount' => MembershipEnquiry::count(),
            'selectedEnquiry' => $this->selectedId
                ? MembershipEnquiry::find($this->selectedId)
                : null,
        ];
    }
};
?>

<x-admin-ui.layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Membership Enquiries</h1>
                <p class="mt-1 text-sm text-slate-500">Review applications from real estate agencies and property developers.</p>
            </div>
            <div class="flex gap-3">
                <span class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm">Total: {{ $totalCount }}</span>
                <span class="rounded-lg bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-800">Unread: {{ $unreadCount }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <input wire:model.live.debounce.350ms="search" type="search"
                       placeholder="Search company, contact, email or location..."
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
                <select wire:model.live="filter"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm">
                    <option value="all">All enquiries</option>
                    <option value="unread">Unread</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="approved">Approved</option>
                    <option value="declined">Declined</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full min-w-[750px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Company</th>
                        <th class="px-5 py-4">Contact</th>
                        <th class="px-5 py-4">Location</th>
                        <th class="px-5 py-4">Platform</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4">Received</th>
                        <th class="px-5 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($enquiries as $enquiry)
                        <tr wire:key="enquiry-{{ $enquiry->id }}" class="{{ $enquiry->read_at === null ? 'bg-amber-50/60' : '' }}">
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $enquiry->company_name }}</div>
                                @if ($enquiry->read_at === null)
                                    <span class="mt-1 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Unread</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div>{{ $enquiry->contact_name }}</div>
                                <div class="text-xs text-slate-500">{{ $enquiry->email }}</div>
                            </td>
                            <td class="px-5 py-4">{{ $enquiry->location }}</td>
                            <td class="px-5 py-4">{{ ucfirst($enquiry->website_platform) }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ ucfirst($enquiry->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-500">{{ $enquiry->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <button wire:click="openEnquiry({{ $enquiry->id }})"
                                        class="font-semibold text-blue-700 hover:underline">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center text-slate-500">No membership enquiries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $enquiries->links() }}</div>

        @if ($selectedEnquiry)
            <div class="rounded-xl border border-blue-200 bg-white p-6 shadow-sm sm:p-8" wire:key="selected-{{ $selectedEnquiry->id }}">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-bold text-slate-900">Application Details</h2>
                    <button wire:click="closeEnquiry" class="text-sm font-semibold text-slate-500 hover:text-slate-900">Close</button>
                </div>

                <div class="mt-6 grid gap-5 text-sm sm:grid-cols-2">
                    <div><div class="text-slate-500">Company</div><div class="mt-1 font-semibold">{{ $selectedEnquiry->company_name }}</div></div>
                    <div><div class="text-slate-500">Contact</div><div class="mt-1 font-semibold">{{ $selectedEnquiry->contact_name }}</div></div>
                    <div><div class="text-slate-500">Email</div><div class="mt-1">{{ $selectedEnquiry->email }}</div></div>
                    <div><div class="text-slate-500">Phone / WhatsApp</div><div class="mt-1">{{ $selectedEnquiry->phone }}</div></div>
                    <div><div class="text-slate-500">Business Type</div><div class="mt-1">{{ ucfirst($selectedEnquiry->business_type) }}</div></div>
                    <div><div class="text-slate-500">Location</div><div class="mt-1">{{ $selectedEnquiry->location }}</div></div>
                    <div><div class="text-slate-500">Website</div><div class="mt-1 break-all">{{ $selectedEnquiry->website_url ?: 'Not provided' }}</div></div>
                    <div><div class="text-slate-500">Platform / Theme</div><div class="mt-1">{{ ucfirst($selectedEnquiry->website_platform) }}{{ $selectedEnquiry->wordpress_theme ? ' — '.$selectedEnquiry->wordpress_theme : '' }}</div></div>
                </div>

                @if ($selectedEnquiry->message)
                    <div class="mt-6 rounded-lg bg-slate-50 p-5">
                        <div class="text-sm font-semibold text-slate-700">Applicant Message</div>
                        <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">{{ $selectedEnquiry->message }}</p>
                    </div>
                @endif

                <div class="mt-8 border-t border-slate-200 pt-6">
                    <h3 class="text-lg font-bold text-slate-900">Administrator Review</h3>
                    <div class="mt-5 space-y-5">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Application Status</label>
                            <select wire:model="applicationStatus" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3">
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="approved">Approved</option>
                                <option value="declined">Declined</option>
                            </select>
                            @error('applicationStatus') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Private Admin Notes</label>
                            <textarea wire:model="adminNotes" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3" placeholder="Internal notes about this application..."></textarea>
                            @error('adminNotes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <button wire:click="saveEnquiry"
                                    class="rounded-lg bg-blue-700 px-6 py-3 font-semibold text-white hover:bg-blue-800">
                                Save Review
                            </button>
                            @if ($saved)
                                <span class="text-sm font-semibold text-green-700">Review saved successfully.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-admin-ui.layout>