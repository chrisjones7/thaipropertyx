<?php

use App\Models\NotificationPreference;
use Livewire\Component;

new class extends Component
{
    public string $activeTab = 'activity';

    public array $emailPreferences = [];

    public array $notificationEvents = [
        'property_imported' => [
            'label' => 'Property Imported',
            'description' => 'Another agency imports one of your properties.',
        ],
        'property_updated' => [
            'label' => 'Property Updated',
            'description' => 'A shared property receives an update.',
        ],
        'property_unavailable' => [
            'label' => 'Property Sold or Withdrawn',
            'description' => 'A shared property is sold or withdrawn.',
        ],
        'syndication_created' => [
            'label' => 'New Syndication',
            'description' => 'A new property syndication is created.',
        ],
        'syndication_removed' => [
            'label' => 'Syndication Removed',
            'description' => 'An existing property syndication is removed.',
        ],
    ];

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless($user && $user->isActive(), 403);

        foreach ($this->notificationEvents as $event => $details) {
            $this->emailPreferences[$event] = true;
        }

        if (! $user->isAgencyAdmin()) {
            return;
        }

        $savedPreferences = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->pluck('email_enabled', 'event_type');

        foreach ($savedPreferences as $event => $enabled) {
            if (array_key_exists($event, $this->notificationEvents)) {
                $this->emailPreferences[$event] = (bool) $enabled;
            }
        }
    }

    public function showTab(string $tab): void
    {
        if (in_array($tab, ['activity', 'preferences'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()
            ->notifications()
            ->whereKey($notificationId)
            ->first();

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }

        $this->activeTab = 'activity';
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        $this->activeTab = 'activity';
    }

    public function savePreferences(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->isAgencyAdmin() && $user->isActive(),
            403
        );

        foreach ($this->notificationEvents as $event => $details) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'event_type' => $event,
                ],
                [
                    'email_enabled' =>
                        ($this->emailPreferences[$event] ?? false) === true,
                ]
            );
        }

        session()->flash(
            'notification_preferences_saved',
            'Your notification preferences have been saved.'
        );

        $this->activeTab = 'preferences';
    }

    public function with(): array
    {
        $user = auth()->user();

        return [
            'activityNotifications' => $user
                ->notifications()
                ->latest()
                ->limit(50)
                ->get(),

            'unreadCount' => $user
                ->unreadNotifications()
                ->count(),
        ];
    }
};
?>

<x-agency-ui.layout title="Notifications">
    <div class="max-w-5xl mx-auto space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Notifications
            </h1>

            <p class="text-sm text-slate-500 mt-2">
                View agency activity and manage email notification preferences.
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">

            <div class="flex gap-6 px-6 border-b border-slate-200">

                <button
                    type="button"
                    wire:click="showTab('activity')"
                    class="py-4 text-sm font-semibold border-b-2 transition
                    {{ $activeTab === 'activity'
                        ? 'border-amber-500 text-slate-900'
                        : 'border-transparent text-slate-500 hover:text-slate-900' }}"
                >
                    Activity

                    @if ($unreadCount > 0)
                        <span class="ml-2 rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </button>

                <button
                    type="button"
                    wire:click="showTab('preferences')"
                    class="py-4 text-sm font-semibold border-b-2 transition
                    {{ $activeTab === 'preferences'
                        ? 'border-amber-500 text-slate-900'
                        : 'border-transparent text-slate-500 hover:text-slate-900' }}"
                >
                    Preferences
                </button>

            </div>

            @if ($activeTab === 'activity')

                <div class="p-6 space-y-5">

                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">
                                Recent Activity
                            </h2>

                            <p class="text-sm text-slate-500 mt-1">
                                {{ $unreadCount }} unread notification{{ $unreadCount === 1 ? '' : 's' }}
                            </p>
                        </div>

                        @if ($unreadCount > 0)
                            <button
                                type="button"
                                wire:click="markAllAsRead"
                                class="text-sm font-semibold text-amber-700 hover:text-amber-900"
                            >
                                Mark All as Read
                            </button>
                        @endif
                    </div>

                    @forelse ($activityNotifications as $notification)

                        @php
                            $data = $notification->data;
                            $isUnread = is_null($notification->read_at);
                        @endphp

                        <div
                            wire:key="notification-{{ $notification->id }}"
                            class="rounded-xl border p-5
                            {{ $isUnread
                                ? 'border-amber-200 bg-amber-50'
                                : 'border-slate-200 bg-white' }}"
                        >
                            <div class="flex items-start justify-between gap-5">

                                <div class="flex-1 space-y-2">

                                    <div class="flex items-center gap-3 flex-wrap">
                                        <h3 class="text-sm font-bold text-slate-900">
                                            {{ $data['title'] ?? 'TPX Notification' }}
                                        </h3>

                                        @if ($isUnread)
                                            <span class="rounded-full bg-amber-200 px-2 py-1 text-xs font-semibold text-amber-900">
                                                Unread
                                            </span>
                                        @endif
                                    </div>

                                    <p class="text-sm text-slate-600">
                                        {{ $data['message'] ?? 'You have a new notification.' }}
                                    </p>

                                   

                                    <p class="text-xs text-slate-400">
                                        {{ $notification->created_at->format('d M Y, H:i') }}
                                    </p>

                                </div>

                                @if ($isUnread)
                                    <button
                                        type="button"
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        class="shrink-0 text-xs font-semibold text-amber-700 hover:text-amber-900"
                                    >
                                        Mark as Read
                                    </button>
                                @endif

                            </div>
                        </div>

                    @empty

                        <div class="py-12 text-center">

                            <svg
                                class="w-10 h-10 mx-auto mb-4 text-slate-400"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"
                                />
                            </svg>

                            <h2 class="text-lg font-semibold text-slate-900">
                                No notifications yet
                            </h2>

                            <p class="text-sm text-slate-500 mt-2">
                                Property and syndication activity will appear here.
                            </p>

                        </div>

                    @endforelse

                </div>

            @else

                <div class="p-6 space-y-6">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Email Notification Preferences
                        </h2>

                        <p class="text-sm text-slate-500 mt-2">
                            Choose which events should trigger an email notification.
                        </p>
                    </div>

                    @if (auth()->user()->isAgencyAdmin())

                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">

                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Registered Administrator Email
                            </p>

                            <p class="text-sm font-semibold text-slate-900 mt-2">
                                {{ auth()->user()->email }}
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                Notification emails will be sent to this address.
                            </p>

                        </div>

                        @if (session()->has('notification_preferences_saved'))
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                                {{ session('notification_preferences_saved') }}
                            </div>
                        @endif

                        <form wire:submit="savePreferences" class="space-y-5">

                            @foreach ($notificationEvents as $event => $details)

                                <label class="flex items-start justify-between gap-5 py-3 border-b border-slate-100 cursor-pointer">

                                    <span class="flex-1">

                                        <span class="block text-sm font-semibold text-slate-900">
                                            {{ $details['label'] }}
                                        </span>

                                        <span class="block text-sm text-slate-500 mt-1">
                                            {{ $details['description'] }}
                                        </span>

                                    </span>

                                    <input
                                        type="checkbox"
                                        wire:model="emailPreferences.{{ $event }}"
                                        class="mt-1 h-5 w-5 rounded border-slate-300 text-amber-500 focus:ring-amber-400"
                                    >

                                </label>

                            @endforeach

                            <div class="flex justify-end pt-3">

                                <button
                                    type="submit"
                                    class="rounded-xl bg-amber-400 px-6 py-3 text-sm font-bold text-slate-950 hover:bg-amber-300 transition"
                                >
                                    Save Preferences
                                </button>

                            </div>

                        </form>

                    @else

                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-5">
                            <p class="text-sm text-amber-900">
                                Only your agency administrator can manage email notification preferences.
                            </p>
                        </div>

                    @endif

                </div>

            @endif

        </div>
    </div>
</x-agency-ui.layout>
