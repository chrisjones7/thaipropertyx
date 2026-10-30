<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public bool $showAddForm = false;
    public string $successMessage = '';

    public string $name = '';
    public string $email = '';
    public string $role = 'agent';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isAgencyAdmin()
            && Auth::user()->agency_id,
            403
        );
    }

    public function openAddForm(): void
    {
        $this->resetValidation();

        $this->reset([
            'name',
            'email',
            'password',
            'password_confirmation',
        ]);

        $this->role = 'agent';
        $this->showAddForm = true;
    }

    public function closeAddForm(): void
    {
        $this->showAddForm = false;
        $this->resetValidation();
    }

    public function createTeamMember(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isAgencyAdmin()
            && Auth::user()->agency_id,
            403
        );

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:agent'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'agency_id' => Auth::user()->agency_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => 'active',
            'password' => $validated['password'],
        ]);

        $this->reset([
            'name',
            'email',
            'password',
            'password_confirmation',
        ]);

        $this->role = 'agent';
        $this->showAddForm = false;
        $this->successMessage = 'Team member added successfully.';
    }

    public bool $showEditForm = false;

    public ?int $editingUserId = null;
    public string $editName = '';
    public string $editEmail = '';
    public string $editRole = 'agent';
    public string $editStatus = 'active';
    public string $editPassword = '';
    public string $editPassword_confirmation = '';

    public function openEditForm(int $userId): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isAgencyAdmin()
            && Auth::user()->agency_id,
            403
        );

        $member = User::query()
            ->where('agency_id', Auth::user()->agency_id)
            ->whereKey($userId)
            ->firstOrFail();

        $this->resetValidation();

        $this->editingUserId = $member->id;
        $this->editName = $member->name;
        $this->editEmail = $member->email;
        $this->editRole = $member->role;
        $this->editStatus = $member->status;
        $this->editPassword = '';
        $this->editPassword_confirmation = '';

        $this->showAddForm = false;
        $this->showEditForm = true;
    }

    public function closeEditForm(): void
    {
        $this->showEditForm = false;
        $this->editingUserId = null;

        $this->reset([
            'editName',
            'editEmail',
            'editPassword',
            'editPassword_confirmation',
        ]);

        $this->editRole = 'agent';
        $this->editStatus = 'active';

        $this->resetValidation();
    }

    public function updateTeamMember(): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isAgencyAdmin()
            && Auth::user()->agency_id
            && $this->editingUserId,
            403
        );

        $member = User::query()
            ->where('agency_id', Auth::user()->agency_id)
            ->whereKey($this->editingUserId)
            ->firstOrFail();

        $rules = [
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $member->id,
            ],
            'editRole' => ['required', 'in:agency_admin,agent'],
            'editStatus' => ['required', 'in:pending,active,suspended,disabled'],
            'editPassword' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        $validated = $this->validate($rules);

        /*
         * Protect the currently logged-in Agency Admin
         * from accidentally removing their own access.
         */
        if ($member->id === Auth::id()) {
            $validated['editRole'] = 'agency_admin';
            $validated['editStatus'] = 'active';
        }

        $member->name = $validated['editName'];
        $member->email = $validated['editEmail'];
        $member->role = $validated['editRole'];
        $member->status = $validated['editStatus'];

        if (!empty($validated['editPassword'])) {
            $member->password = $validated['editPassword'];
        }

        $member->save();

        $this->closeEditForm();
        $this->successMessage = 'Team member updated successfully.';
    }

    public function deleteTeamMember(int $userId): void
    {
        abort_unless(
            Auth::check()
            && Auth::user()->isAgencyAdmin()
            && Auth::user()->agency_id,
            403
        );

        $member = User::query()
            ->where('agency_id', Auth::user()->agency_id)
            ->whereKey($userId)
            ->firstOrFail();

        // Agency Admin accounts cannot be deleted here.
        abort_if($member->role === 'agency_admin', 403);

        // An administrator cannot delete their own account.
        abort_if($member->id === Auth::id(), 403);

        $member->delete();

        $this->successMessage = 'Team member deleted successfully.';
    }

    public function with(): array
    {
        return [
            'teamMembers' => User::query()
                ->where('agency_id', Auth::user()->agency_id)
                ->orderByRaw("CASE WHEN role = 'agency_admin' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get(),
        ];
    }
};
?>

<div>
<x-agency-ui.layout>
    <div class="space-y-6">

        @if($successMessage !== '')
            <div
                class="flex items-center justify-between gap-4 rounded-xl px-5 py-4"
                style="background-color: #ecfdf5; border: 1px solid #a7f3d0;"
            >
                <div class="flex items-center gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                        style="background-color: #10b981; color: #ffffff;"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            style="width: 18px; height: 18px;"
                            aria-hidden="true"
                        >
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                    </div>

                    <div>
                        <p class="text-sm font-bold" style="color: #065f46;">
                            Success
                        </p>

                        <p class="mt-0.5 text-sm" style="color: #047857;">
                            {{ $successMessage }}
                        </p>
                    </div>

                </div>

                <button
                    type="button"
                    wire:click="$set('successMessage', '')"
                    class="flex h-8 w-8 items-center justify-center rounded-full text-xl leading-none transition hover:bg-emerald-100"
                    style="color: #059669;"
                    aria-label="Dismiss success message"
                >
                    &times;
                </button>

            </div>
        @endif

        {{-- Page heading --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Team & Agents
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Manage the people who have access to your agency account.
                </p>
            </div>

            <button
                type="button"
                wire:click="openAddForm"
                class="inline-flex items-center justify-center gap-2.5 rounded-xl px-6 py-3 text-sm font-bold shadow-sm transition hover:shadow-md"
                style="background-color: #2563eb; color: #ffffff; border: 1px solid #1d4ed8; padding-top: 12px; padding-bottom: 12px;"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="w-5 h-5"
                    aria-hidden="true"
                >
                    <path d="M12 5v14M5 12h14"/>
                </svg>

                Add Team Member
            </button>
        </div>

        @if($showAddForm)

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                {{-- Form header --}}
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-7 py-5">

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
                            style="background-color: #dbeafe; color: #2563eb;"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                style="width: 24px; height: 24px;"
                                aria-hidden="true"
                            >
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Add Team Member
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Create a new user for your agency. They will be able to log in and access agency features.
                            </p>
                        </div>

                    </div>

                    <button
                        type="button"
                        wire:click="closeAddForm"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-xl text-slate-400 transition hover:bg-slate-200 hover:text-slate-700"
                        aria-label="Close"
                    >
                        &times;
                    </button>

                </div>

                <form wire:submit="createTeamMember" class="px-7 py-6">

                    {{-- Name + Email --}}
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Full Name
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="flex overflow-hidden rounded-xl border border-slate-300 bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">

                                <div class="flex w-12 shrink-0 items-center justify-center border-r border-slate-200 text-slate-400">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        style="width: 19px; height: 19px;"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>

                                <input
                                    type="text"
                                    wire:model="name"
                                    autocomplete="name"
                                    placeholder="Enter full name"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-4 text-sm text-slate-900 outline-none"
                                    style="height: 48px;"
                                >

                            </div>

                            @error('name')
                                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Email Address
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="flex overflow-hidden rounded-xl border border-slate-300 bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">

                                <div class="flex w-12 shrink-0 items-center justify-center border-r border-slate-200 text-slate-400">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        style="width: 19px; height: 19px;"
                                    >
                                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                                        <path d="m3 7 9 6 9-6"/>
                                    </svg>
                                </div>

                                <input
                                    type="email"
                                    wire:model="email"
                                    autocomplete="email"
                                    placeholder="Enter email address"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-4 text-sm text-slate-900 outline-none"
                                    style="height: 48px;"
                                >

                            </div>

                            @error('email')
                                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- Role + Passwords --}}
                    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Role
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                wire:model="role"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                style="height: 48px;"
                            >
                                <option value="agent">Agent</option>

                            </select>

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Agents can manage agency properties. Agency Admins have access to agency settings and team management.
                            </p>

                            @error('role')
                                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Password
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="password"
                                wire:model="password"
                                autocomplete="new-password"
                                placeholder="Minimum 8 characters"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                style="height: 48px;"
                            >

                            @error('password')
                                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Confirm Password
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="password"
                                wire:model="password_confirmation"
                                autocomplete="new-password"
                                placeholder="Confirm password"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                style="height: 48px;"
                            >
                        </div>

                    </div>

                    {{-- Agency notice --}}
                    <div
                        class="mt-6 flex items-start gap-3 rounded-xl px-5 py-4"
                        style="background-color: #eff6ff; border: 1px solid #bfdbfe;"
                    >
                        <div
                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                            style="background-color: #2563eb; color: #ffffff;"
                        >
                            i
                        </div>

                        <div>
                            <p class="text-sm font-bold text-slate-800">
                                Important
                            </p>

                            <p class="mt-1 text-sm text-slate-600">
                                The new team member will be added to
                                <strong class="font-semibold text-slate-800">
                                    {{ Auth::user()->agency->name }}
                                </strong>
                                and will have an active account.
                            </p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-5">

                        <button
                            type="button"
                            wire:click="closeAddForm"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            <span aria-hidden="true">&times;</span>
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 text-sm font-bold shadow-sm transition"
                            style="background-color: #2563eb; color: #ffffff; border: 1px solid #1d4ed8;"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                style="width: 17px; height: 17px;"
                                aria-hidden="true"
                            >
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M19 8v6M22 11h-6"/>
                            </svg>

                            Add Team Member
                        </button>

                    </div>

                </form>

            </div>

        @endif

        {{-- Edit Team Member --}}
        @if($showEditForm)

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-7 py-5">

                    <div class="flex items-center gap-4">

                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
                            style="background-color: #ede9fe; color: #7c3aed;"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                style="width: 23px; height: 23px;"
                                aria-hidden="true"
                            >
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                Edit Team Member
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Update account details, permissions and access status.
                            </p>
                        </div>

                    </div>

                    <button
                        type="button"
                        wire:click="closeEditForm"
                        class="flex h-9 w-9 items-center justify-center rounded-full text-xl text-slate-400 transition hover:bg-slate-200 hover:text-slate-700"
                        aria-label="Close"
                    >
                        &times;
                    </button>

                </div>

                <form wire:submit="updateTeamMember" class="px-7 py-6">

                    {{-- Name + Email --}}
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Full Name <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                wire:model="editName"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                style="height: 48px;"
                            >

                            @error('editName')
                                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Email Address <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="email"
                                wire:model="editEmail"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                style="height: 48px;"
                            >

                            @error('editEmail')
                                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- Role + Status --}}
                    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Role <span class="text-red-500">*</span>
                            </label>

                            <select
                                wire:model="editRole"
                                @if($editingUserId === Auth::id()) disabled @endif
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-slate-100 disabled:text-slate-500"
                                style="height: 48px;"
                            >
                                <option value="agent">Agent</option>
                                <option value="agency_admin">Agency Admin</option>

                            </select>

                            @if($editingUserId === Auth::id())
                                <p class="mt-2 text-xs text-slate-500">
                                    You cannot remove your own Agency Admin role.
                                </p>
                            @endif

                            @error('editRole')
                                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Account Status <span class="text-red-500">*</span>
                            </label>

                            <select
                                wire:model="editStatus"
                                @if($editingUserId === Auth::id()) disabled @endif
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-slate-100 disabled:text-slate-500"
                                style="height: 48px;"
                            >
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                                <option value="disabled">Disabled</option>
                            </select>

                            @if($editingUserId === Auth::id())
                                <p class="mt-2 text-xs text-slate-500">
                                    You cannot disable or suspend your own account.
                                </p>
                            @endif

                            @error('editStatus')
                                <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- Password --}}
                    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <div class="mb-4">
                            <h3 class="text-sm font-bold text-slate-800">
                                Reset Password
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Set a new password for this team member. Leave both fields blank if you do not want to reset their password.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    New Password
                                </label>

                                <input
                                    type="password"
                                    wire:model="editPassword"
                                    autocomplete="new-password"
                                    placeholder="Minimum 8 characters"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    style="height: 48px;"
                                >

                                @error('editPassword')
                                    <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Confirm New Password
                                </label>

                                <input
                                    type="password"
                                    wire:model="editPassword_confirmation"
                                    autocomplete="new-password"
                                    placeholder="Confirm new password"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    style="height: 48px;"
                                >
                            </div>

                        </div>

                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5">

                        <button
                            type="button"
                            wire:click="closeEditForm"
                            class="rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-xl px-6 py-3 text-sm font-bold shadow-sm"
                            style="background-color: #2563eb; color: #ffffff; border: 1px solid #1d4ed8;"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        @endif


        {{-- Team table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            {{-- Team header --}}
            <div class="flex items-center justify-between gap-6 border-b border-slate-200 px-7 py-5">

                <div class="flex items-center gap-4">

                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full"
                        style="background-color: #dcfce7; color: #16a34a;"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            style="width: 21px; height: 21px;"
                            aria-hidden="true"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Agency Team
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $teamMembers->count() }}
                            {{ $teamMembers->count() === 1 ? 'team member' : 'team members' }}
                        </p>
                    </div>

                </div>

                <div class="text-sm text-slate-500">
                    Manage your agency users and permissions
                </div>

            </div>

            @if($teamMembers->isEmpty())

                <div class="px-7 py-14 text-center">

                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full"
                        style="background-color: #f1f5f9; color: #64748b;"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            style="width: 22px; height: 22px;"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                        </svg>
                    </div>

                    <p class="mt-4 font-bold text-slate-800">
                        No team members found
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Add your first agent to begin building your agency team.
                    </p>

                </div>

            @else

                <div class="w-full overflow-x-auto">

                    <table class="w-full table-fixed">

                        <colgroup>
                            <col style="width: 42%;">
                            <col style="width: 20%;">
                            <col style="width: 20%;">
                            <col style="width: 18%;">
                        </colgroup>

                        <thead>
                            <tr style="background-color: #f8fafc;">

                                <th class="border-b border-slate-200 px-7 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Team Member
                                </th>

                                <th class="border-b border-slate-200 px-7 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Role
                                </th>

                                <th class="border-b border-slate-200 px-7 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>

                                <th class="border-b border-slate-200 px-7 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($teamMembers as $member)

                                <tr class="transition hover:bg-slate-50">

                                    <td class="px-7 py-5">

                                        <div class="flex items-center gap-4">

                                            <div
                                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                                style="background-color: #f1f5f9; color: #475569;"
                                            >
                                                {{ strtoupper(substr($member->name, 0, 1)) }}
                                            </div>

                                            <div class="min-w-0">

                                                <div class="flex items-center gap-2">

                                                    <span class="truncate font-bold text-slate-900">
                                                        {{ $member->name }}
                                                    </span>

                                                    @if($member->id === Auth::id())
                                                        <span
                                                            class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-bold"
                                                            style="background-color: #dbeafe; color: #2563eb;"
                                                        >
                                                            You
                                                        </span>
                                                    @endif

                                                </div>

                                                <div class="mt-1 truncate text-sm text-slate-500">
                                                    {{ $member->email }}
                                                </div>

                                            </div>

                                        </div>

                                    </td>

                                    <td class="px-7 py-5">

                                        @if($member->role === 'agency_admin')

                                            <span
                                                class="inline-flex whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold"
                                                style="background-color: #eff6ff; color: #2563eb;"
                                            >
                                                Agency Admin
                                            </span>

                                        @else

                                            <span
                                                class="inline-flex whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold"
                                                style="background-color: #f1f5f9; color: #475569;"
                                            >
                                                Agent
                                            </span>

                                        @endif

                                    </td>

                                    <td class="px-7 py-5">

                                        @if($member->status === 'active')

                                            <span
                                                class="inline-flex items-center gap-2 whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold"
                                                style="background-color: #ecfdf5; color: #047857;"
                                            >
                                                <span
                                                    class="h-2 w-2 rounded-full"
                                                    style="background-color: #10b981;"
                                                ></span>
                                                Active
                                            </span>

                                        @elseif($member->status === 'pending')

                                            <span
                                                class="inline-flex whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold"
                                                style="background-color: #fffbeb; color: #b45309;"
                                            >
                                                Pending
                                            </span>

                                        @elseif($member->status === 'suspended')

                                            <span
                                                class="inline-flex whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold"
                                                style="background-color: #fff7ed; color: #c2410c;"
                                            >
                                                Suspended
                                            </span>

                                        @else

                                            <span
                                                class="inline-flex whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold"
                                                style="background-color: #f1f5f9; color: #64748b;"
                                            >
                                                Disabled
                                            </span>

                                        @endif

                                    </td>

                                    <td class="px-7 py-5 text-right">

                                        <button
                                            type="button"
                                            wire:click="openEditForm({{ $member->id }})"
                                            class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-bold transition hover:bg-blue-50"
                                            style="color: #2563eb;"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                style="width: 16px; height: 16px;"
                                                aria-hidden="true"
                                            >
                                                <path d="M12 20h9"/>
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/>
                                            </svg>

                                            Edit
                                        </button>

                                        @if($member->role === 'agent')
                                            <button
                                                type="button"
                                                wire:click="deleteTeamMember({{ $member->id }})"
                                                wire:confirm="Delete {{ $member->name }} permanently? This cannot be undone."
                                                class="ml-2 inline-flex items-center justify-center px-3 py-2 text-sm font-bold transition hover:underline"
                                                style="color: #dc2626;"
                                            >
                                                Delete
                                            </button>
                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>
    </div>
</x-agency-ui.layout>
</div>
