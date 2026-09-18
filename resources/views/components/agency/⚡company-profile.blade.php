<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $agency;
    public $logoUpload;

    public string $name = '';
    public string $legal_name = '';
    public string $registration_number = '';
    public string $email = '';
    public string $phone = '';
    public string $website = '';
    public string $description = '';
    public string $address_line_1 = '';
    public string $address_line_2 = '';
    public string $district = '';
    public string $province = '';
    public string $postcode = '';
    public string $country = '';

    public function mount(): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless(
            $user->isAgencyAdmin() && $user->agency_id,
            403
        );

        $this->agency = $user->agency;

        abort_unless($this->agency, 403);

        $this->name = $this->agency->name ?? '';
        $this->legal_name = $this->agency->legal_name ?? '';
        $this->registration_number = $this->agency->registration_number ?? '';
        $this->email = $this->agency->email ?? '';
        $this->phone = $this->agency->phone ?? '';
        $this->website = $this->agency->website ?? '';
        $this->description = $this->agency->description ?? '';
        $this->address_line_1 = $this->agency->address_line_1 ?? '';
        $this->address_line_2 = $this->agency->address_line_2 ?? '';
        $this->district = $this->agency->district ?? '';
        $this->province = $this->agency->province ?? '';
        $this->postcode = $this->agency->postcode ?? '';
        $this->country = $this->agency->country ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:150'],
            'province' => ['nullable', 'string', 'max:150'],
            'postcode' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'logoUpload' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($validated['logoUpload']);

        if ($this->logoUpload) {
            if ($this->agency->logo) {
                Storage::disk('public')->delete($this->agency->logo);
            }

            $validated['logo'] = $this->logoUpload->store(
                'agency-logos',
                'public'
            );
        }

        $this->agency->update($validated);
        $this->agency->refresh();

        $this->logoUpload = null;

        session()->flash(
            'success',
            'Company profile and branding updated successfully.'
        );
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

<div>
<x-agency-ui.layout title="Company Profile & Branding">

    <div class="max-w-6xl">

        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 mb-8">

            <div>
                <p class="text-sm font-semibold text-amber-600">
                    My Agency
                </p>

                <h1 class="mt-2 text-3xl font-bold text-slate-900">
                    Company Profile & Branding
                </h1>

                <p class="mt-2 text-slate-500 max-w-2xl">
                    Manage the company information and branding displayed throughout your ThaiPropertyX agency portal.
                </p>
            </div>

            @if($agency->verified)
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Verified Agency
                </span>
            @endif

        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="save">

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                {{-- Branding --}}
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

                    <h2 class="text-lg font-bold text-slate-900">
                        Company Branding
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Your logo appears throughout your agency portal.
                    </p>

                    <div class="mt-6 rounded-2xl bg-slate-950 p-6 flex items-center justify-center min-h-44">

                        @if($logoUpload)
                            <img
                                src="{{ $logoUpload->temporaryUrl() }}"
                                class="max-h-28 max-w-full object-contain"
                                alt="Logo preview"
                            >
                        @elseif($agency->logo)
                            <img
                                src="{{ asset('storage/' . $agency->logo) }}"
                                class="max-h-28 max-w-full object-contain"
                                alt="{{ $agency->name }}"
                            >
                        @else
                            <div class="text-center">
                                <div class="mx-auto w-16 h-16 rounded-2xl bg-amber-400 text-slate-950 flex items-center justify-center font-black text-xl">
                                    TPX
                                </div>

                                <p class="mt-3 text-sm text-slate-400">
                                    No company logo uploaded
                                </p>
                            </div>
                        @endif

                    </div>

                    <label class="block mt-5 text-sm font-semibold text-slate-700">
                        Company Logo
                    </label>

                    <input
                        type="file"
                        wire:model="logoUpload"
                        accept="image/png,image/jpeg,image/webp"
                        class="mt-2 block w-full text-sm text-slate-600"
                    >

                    <p class="mt-2 text-xs text-slate-400">
                        PNG, JPG or WebP. Maximum 2 MB. Transparent PNG recommended.
                    </p>

                    @error('logoUpload')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                </div>

                {{-- Company Details --}}
                <div class="xl:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

                    <h2 class="text-lg font-bold text-slate-900">
                        Company Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-6">

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Trading Name
                            </label>
                            <input wire:model="name" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Legal Company Name
                            </label>
                            <input wire:model="legal_name" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Registration Number
                            </label>
                            <input wire:model="registration_number" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Website
                            </label>
                            <input wire:model="website" type="url" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                            @error('website') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Email
                            </label>
                            <input wire:model="email" type="email" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Phone
                            </label>
                            <input wire:model="phone" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700">
                                Company Description
                            </label>
                            <textarea wire:model="description" rows="4" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3"></textarea>
                        </div>

                    </div>

                </div>

                {{-- Address --}}
                <div class="xl:col-span-3 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">

                    <h2 class="text-lg font-bold text-slate-900">
                        Office Address
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mt-6">

                        <div class="lg:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700">
                                Address
                            </label>
                            <input wire:model="address_line_1" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Address Line 2
                            </label>
                            <input wire:model="address_line_2" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                District
                            </label>
                            <input wire:model="district" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Province
                            </label>
                            <input wire:model="province" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Postcode
                            </label>
                            <input wire:model="postcode" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">
                                Country
                            </label>
                            <input wire:model="country" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3">
                        </div>

                    </div>

                </div>

            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="submit"
                    class="rounded-xl bg-slate-950 px-6 py-3 font-semibold text-white hover:bg-slate-800 transition"
                >
                    Save Company Profile
                </button>
            </div>

        </form>

    </div>

</x-agency-ui.layout>
</div>
