<?php

use App\Models\Agency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $name = '';
    public string $legal_name = '';
    public string $registration_number = '';

    public string $email = '';
    public string $phone = '';
    public string $website = '';

    public string $address_line_1 = '';
    public string $address_line_2 = '';
    public string $district = '';
    public string $province = '';
    public string $postcode = '';
    public string $country = 'Thailand';

    public string $description = '';

    public string $status = 'pending';
    public bool $verified = false;

    public function mount(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/login', navigate: true);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],

            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],

            'description' => ['nullable', 'string', 'max:5000'],

            'status' => [
                'required',
                Rule::in(['pending', 'active', 'suspended', 'disabled']),
            ],

            'verified' => ['boolean'],
        ]);

        $slugBase = Str::slug($validated['name']);
        $slug = $slugBase;
        $counter = 2;

        while (Agency::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $counter;
            $counter++;
        }

        $validated['slug'] = $slug;

        $validated['verified_at'] = $validated['verified']
            ? now()
            : null;

        Agency::create($validated);

        session()->flash(
            'success',
            'Agency created successfully.'
        );

        $this->redirect('/admin/agencies', navigate: true);
    }
};
?>

<x-admin-ui.layout title="Add Agency">

    <div class="mx-auto max-w-6xl">

        {{-- Page heading --}}
        <div class="mb-10">

            <a
                href="/admin/agencies"
                wire:navigate
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-slate-900"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/>
                </svg>

                Back to Agencies
            </a>

            <div class="mt-6">
                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                    Exchange Network
                </p>

                <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    Add Agency
                </h3>

                <p class="mt-3 max-w-2xl text-slate-500">
                    Create a new property agency account for the ThaiPropertyX exchange.
                </p>
            </div>

        </div>

        <form wire:submit="save">

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">

                {{-- Main column --}}
                <div class="space-y-8 xl:col-span-2">

                    {{-- Company Details --}}
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-7 py-6">
                            <h4 class="text-lg font-semibold text-slate-900">
                                Company Details
                            </h4>

                            <p class="mt-2 text-sm text-slate-500">
                                Basic information identifying the agency.
                            </p>
                        </div>

                        <div class="p-7">

                            <div class="space-y-7">

                                <div>
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Agency Name
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        wire:model="name"
                                        type="text"
                                        placeholder="Example: Hua Hin Property Group"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    >

                                    @error('name')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 gap-7 md:grid-cols-2">

                                    <div>
                                        <label class="mb-3 block text-sm font-semibold text-slate-700">
                                            Legal Company Name
                                        </label>

                                        <input
                                            wire:model="legal_name"
                                            type="text"
                                            placeholder="Registered company name"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('legal_name')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-3 block text-sm font-semibold text-slate-700">
                                            Registration Number
                                        </label>

                                        <input
                                            wire:model="registration_number"
                                            type="text"
                                            placeholder="Company registration number"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('registration_number')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                </div>

                                <div>
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Description
                                    </label>

                                    <textarea
                                        wire:model="description"
                                        rows="6"
                                        placeholder="Brief description of the agency..."
                                        class="w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    ></textarea>

                                    @error('description')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>

                        </div>

                    </section>

                    {{-- Contact Details --}}
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-7 py-6">
                            <h4 class="text-lg font-semibold text-slate-900">
                                Contact Details
                            </h4>

                            <p class="mt-2 text-sm text-slate-500">
                                Public and administrative contact information.
                            </p>
                        </div>

                        <div class="p-7">

                            <div class="grid grid-cols-1 gap-x-7 gap-y-7 md:grid-cols-2">

                                <div>
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Email Address
                                    </label>

                                    <input
                                        wire:model="email"
                                        type="email"
                                        placeholder="agency@example.com"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    >

                                    @error('email')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Phone Number
                                    </label>

                                    <input
                                        wire:model="phone"
                                        type="text"
                                        placeholder="+66 ..."
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    >

                                    @error('phone')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Website
                                    </label>

                                    <input
                                        wire:model="website"
                                        type="url"
                                        placeholder="https://example.com"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    >

                                    @error('website')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>

                        </div>

                    </section>

                    {{-- Address --}}
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-7 py-6">
                            <h4 class="text-lg font-semibold text-slate-900">
                                Office Address
                            </h4>

                            <p class="mt-2 text-sm text-slate-500">
                                Main business location for the agency.
                            </p>
                        </div>

                        <div class="p-7">

                            <div class="space-y-7">

                                <div>
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Address Line 1
                                    </label>

                                    <input
                                        wire:model="address_line_1"
                                        type="text"
                                        placeholder="Building, road, street address"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    >
                                </div>

                                <div>
                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Address Line 2
                                    </label>

                                    <input
                                        wire:model="address_line_2"
                                        type="text"
                                        placeholder="Unit, floor or additional address"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    >
                                </div>

                                <div class="grid grid-cols-1 gap-x-7 gap-y-7 md:grid-cols-2">

                                    <div>
                                        <label class="mb-3 block text-sm font-semibold text-slate-700">
                                            District
                                        </label>

                                        <input
                                            wire:model="district"
                                            type="text"
                                            placeholder="Hua Hin"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >
                                    </div>

                                    <div>
                                        <label class="mb-3 block text-sm font-semibold text-slate-700">
                                            Province
                                        </label>

                                        <input
                                            wire:model="province"
                                            type="text"
                                            placeholder="Prachuap Khiri Khan"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >
                                    </div>

                                    <div>
                                        <label class="mb-3 block text-sm font-semibold text-slate-700">
                                            Postcode
                                        </label>

                                        <input
                                            wire:model="postcode"
                                            type="text"
                                            placeholder="77110"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >
                                    </div>

                                    <div>
                                        <label class="mb-3 block text-sm font-semibold text-slate-700">
                                            Country
                                            <span class="text-red-500">*</span>
                                        </label>

                                        <input
                                            wire:model="country"
                                            type="text"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('country')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                </div>

                            </div>

                        </div>

                    </section>

                </div>

                {{-- Right column --}}
                <div class="space-y-8">

                    {{-- Account status --}}
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-6 py-6">
                            <h4 class="font-semibold text-slate-900">
                                Account Status
                            </h4>

                            <p class="mt-2 text-sm text-slate-500">
                                Control access and verification.
                            </p>
                        </div>

                        <div class="space-y-7 p-6">

                            <div>
                                <label class="mb-3 block text-sm font-semibold text-slate-700">
                                    Status
                                </label>

                                <select
                                    wire:model="status"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="disabled">Disabled</option>
                                </select>

                                @error('status')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="border-t border-slate-100 pt-6">

                                <label class="flex cursor-pointer items-start gap-4 rounded-xl border border-slate-200 bg-slate-50 p-5 transition hover:border-slate-300">

                                    <input
                                        wire:model="verified"
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-slate-300"
                                    >

                                    <span>
                                        <span class="block text-sm font-semibold text-slate-800">
                                            Verified Agency
                                        </span>

                                        <span class="mt-2 block text-xs leading-5 text-slate-500">
                                            Confirms that ThaiPropertyX has reviewed and verified this agency.
                                        </span>
                                    </span>

                                </label>

                            </div>

                        </div>

                    </section>

                    {{-- Information card --}}
                    <section class="rounded-2xl bg-slate-950 p-7 text-white shadow-sm">

                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">
                            New Agency
                        </p>

                        <h4 class="mt-4 text-xl font-bold">
                            Exchange Membership
                        </h4>

                        <p class="mt-4 text-sm leading-7 text-slate-300">
                            Once created, this agency can be connected to agents,
                            properties, feeds and syndication relationships across
                            the ThaiPropertyX platform.
                        </p>

                        <div class="mt-6 border-t border-slate-800 pt-5">

                            <p class="text-xs uppercase tracking-wider text-slate-500">
                                Initial status
                            </p>

                            <p class="mt-2 text-sm font-semibold text-white">
                                {{ ucfirst($status) }}
                            </p>

                        </div>

                    </section>

                </div>

            </div>

            {{-- Bottom action bar --}}
            <div class="mt-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white px-7 py-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <p class="text-sm font-semibold text-slate-900">
                        Ready to create this agency?
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        You can edit agency information later.
                    </p>
                </div>

                <div class="flex items-center gap-3">

                    <a
                        href="/admin/agencies"
                        wire:navigate
                        class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex min-w-36 items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-3 text-sm font-bold text-slate-950 shadow-sm transition hover:bg-amber-300 disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="save">
                            Create Agency
                        </span>

                        <span wire:loading wire:target="save">
                            Creating...
                        </span>
                    </button>

                </div>

            </div>

        </form>

    </div>

</x-admin-ui.layout>