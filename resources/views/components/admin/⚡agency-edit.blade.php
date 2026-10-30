<?php

use App\Models\Agency;
use App\Models\TaxonomyMapping;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public Agency $agency;

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
    public int $tpxMediaCount = 0;
    public int $externalMediaCount = 0;
    public int $storageBytes = 0;
    public int $activeApiConnectorCount = 0;
    public int $activeIncomingSyndicationCount = 0;
    public int $activeOutgoingSyndicationCount = 0;
    public ?int $agencyAdminUserId = null;
    public string $loginName = '';
    public string $loginEmail = '';
    public string $loginStatus = 'active';
    public string $loginPassword = '';
    public string $loginPasswordConfirmation = '';

    public function mount(Agency $agency): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );

        $this->agency = $agency;
        $agencyAdmin = $agency->users()
            ->where('role', 'agency_admin')
            ->orderBy('id')
            ->first();

        if ($agencyAdmin) {
            $this->agencyAdminUserId = $agencyAdmin->id;
            $this->loginName = $agencyAdmin->name ?? '';
            $this->loginEmail = $agencyAdmin->email ?? '';
            $this->loginStatus = $agencyAdmin->status ?? 'active';
        }
        $this->tpxMediaCount = $agency->properties()
            ->withCount([
                'media as tpx_media_count' => fn ($query) =>
                    $query->whereNotNull('storage_disk'),
            ])
            ->get()
            ->sum('tpx_media_count');

        $this->externalMediaCount = $agency->properties()
            ->withCount([
                'media as external_media_count' => fn ($query) =>
                    $query->whereNull('storage_disk'),
            ])
            ->get()
            ->sum('external_media_count');

        $this->storageBytes = (int) $agency->properties()
            ->withSum([
                'media as media_storage_bytes' => fn ($query) =>
                    $query->whereNotNull('storage_disk'),
            ], 'file_size')
            ->get()
            ->sum('media_storage_bytes');

        $this->activeApiConnectorCount = $agency->apiClients()
            ->where('status', 'active')
            ->count();

        $this->activeIncomingSyndicationCount = $agency->incomingSyndications()
            ->where('status', 'active')
            ->count();

        $this->activeOutgoingSyndicationCount = $agency->outgoingSyndications()
            ->where('status', 'active')
            ->count();

        $this->name = $agency->name ?? '';
        $this->legal_name = $agency->legal_name ?? '';
        $this->registration_number = $agency->registration_number ?? '';

        $this->email = $agency->email ?? '';
        $this->phone = $agency->phone ?? '';
        $this->website = $agency->website ?? '';

        $this->address_line_1 = $agency->address_line_1 ?? '';
        $this->address_line_2 = $agency->address_line_2 ?? '';
        $this->district = $agency->district ?? '';
        $this->province = $agency->province ?? '';
        $this->postcode = $agency->postcode ?? '';
        $this->country = $agency->country ?? 'Thailand';

        $this->description = $agency->description ?? '';

        $this->status = $agency->status ?? 'pending';
        $this->verified = (bool) $agency->verified;
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/login', navigate: true);
    }

    public function saveAgencyLogin(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );

        if (!$this->agencyAdminUserId) {
            $this->addError(
                'loginEmail',
                'This agency does not currently have an Agency Administrator account.'
            );

            return;
        }

        $agencyAdmin = \App\Models\User::query()
            ->where('id', $this->agencyAdminUserId)
            ->where('agency_id', $this->agency->id)
            ->where('role', 'agency_admin')
            ->firstOrFail();

        $validated = $this->validate([
            'loginName' => [
                'required',
                'string',
                'max:255',
            ],

            'loginEmail' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($agencyAdmin->id),
            ],

            'loginStatus' => [
                'required',
                Rule::in([
                    'active',
                    'suspended',
                    'disabled',
                ]),
            ],

            'loginPassword' => [
                'nullable',
                'string',
                'min:8',
                'same:loginPasswordConfirmation',
            ],

            'loginPasswordConfirmation' => [
                'nullable',
                'string',
                'min:8',
            ],
        ]);

        $agencyAdmin->name = $validated['loginName'];
        $agencyAdmin->email = $validated['loginEmail'];
        $agencyAdmin->status = $validated['loginStatus'];

        if (!empty($validated['loginPassword'])) {
            $agencyAdmin->password = $validated['loginPassword'];
        }

        $agencyAdmin->save();

        $this->loginPassword = '';
        $this->loginPasswordConfirmation = '';

        session()->flash(
            'loginSuccess',
            'Agency administrator login updated successfully.'
        );
    }

    public function createAgencyAdmin(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );

        if ($this->agencyAdminUserId) {
            $this->addError(
                'loginEmail',
                'This agency already has an Agency Administrator account.'
            );

            return;
        }

        $existingAdmin = \App\Models\User::query()
            ->where('agency_id', $this->agency->id)
            ->where('role', 'agency_admin')
            ->exists();

        if ($existingAdmin) {
            $this->addError(
                'loginEmail',
                'This agency already has an Agency Administrator account.'
            );

            return;
        }

        $validated = $this->validate([
            'loginName' => [
                'required',
                'string',
                'max:255',
            ],

            'loginEmail' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],

            'loginPassword' => [
                'required',
                'string',
                'min:8',
                'same:loginPasswordConfirmation',
            ],

            'loginPasswordConfirmation' => [
                'required',
                'string',
                'min:8',
            ],
        ]);

        $agencyAdmin = \App\Models\User::create([
            'agency_id' => $this->agency->id,
            'name' => $validated['loginName'],
            'email' => $validated['loginEmail'],
            'password' => $validated['loginPassword'],
            'role' => 'agency_admin',
            'status' => 'active',
        ]);

        $this->agencyAdminUserId = $agencyAdmin->id;
        $this->loginName = $agencyAdmin->name;
        $this->loginEmail = $agencyAdmin->email;
        $this->loginPassword = '';
        $this->loginPasswordConfirmation = '';

        session()->flash(
            'loginSuccess',
            'Agency Administrator account created successfully.'
        );
    }

    public function deleteAgency(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->isPlatformAdmin(),
            403
        );

        abort_if(
            $this->agency->users()
                ->where('role', 'platform_admin')
                ->exists(),
            409,
            'A platform administrator is attached to this agency. Remove that association before deleting the agency.'
        );
        $agencyId = $this->agency->id;

        DB::transaction(function () use ($agencyId) {
            TaxonomyMapping::query()
                ->where('agency_id', $agencyId)
                ->delete();

            $this->agency->users()->delete();

            $this->agency->delete();
        });

        session()->flash(
            'success',
            'Agency and all associated data deleted successfully.'
        );

        $this->redirect('/admin/agencies', navigate: true);
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
                Rule::in([
                    'pending',
                    'active',
                    'suspended',
                    'disabled',
                ]),
            ],

            'verified' => ['boolean'],
        ]);

        /*
         * Keep the slug aligned with the agency name.
         * If the name changes, generate a new unique slug.
         */
        if ($this->agency->name !== $validated['name']) {

            $slugBase = Str::slug($validated['name']);

            if ($slugBase === '') {
                $slugBase = 'agency';
            }

            $slug = $slugBase;
            $counter = 2;

            while (
                Agency::where('slug', $slug)
                    ->where('id', '!=', $this->agency->id)
                    ->exists()
            ) {
                $slug = $slugBase . '-' . $counter;
                $counter++;
            }

            $validated['slug'] = $slug;
        }

        /*
         * Verification timestamp.
         *
         * Preserve the original verification date if the agency
         * was already verified. Set it now when verification is
         * enabled for the first time.
         */
        if ($validated['verified']) {
            $validated['verified_at'] =
                $this->agency->verified_at ?? now();
        } else {
            $validated['verified_at'] = null;
        }

        $this->agency->update($validated);

        session()->flash(
            'success',
            'Agency updated successfully.'
        );

        $this->redirect('/admin/agencies', navigate: true);
    }
};
?>

<x-admin-ui.layout title="Edit Agency">

    <div class="mx-auto max-w-6xl">

        {{-- PAGE HEADING --}}
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
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m15 18-6-6 6-6"
                    />
                </svg>

                Back to Agencies
            </a>

            <div class="mt-6">

                <p class="text-sm font-semibold uppercase tracking-wider text-amber-600">
                    Exchange Network
                </p>

                <h3 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    Edit Agency
                </h3>

                <p class="mt-3 max-w-2xl text-slate-500">
                    Manage the company details, contact information and
                    ThaiPropertyX membership status for
                    <span class="font-semibold text-slate-700">
                        {{ $agency->name }}
                    </span>.
                </p>

            </div>

        </div>


        <form wire:submit="save">

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-3">

                {{-- MAIN COLUMN --}}
                <div class="space-y-8 xl:col-span-2">

                    {{-- COMPANY DETAILS --}}
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

                                {{-- AGENCY NAME --}}
                                <div>

                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Agency Name
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input
                                        wire:model="name"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    >

                                    @error('name')
                                        <p class="mt-2 text-sm text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                <div class="grid grid-cols-1 gap-7 md:grid-cols-2">

                                    {{-- LEGAL NAME --}}
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
                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                    </div>


                                    {{-- REGISTRATION NUMBER --}}
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
                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                    </div>

                                </div>


                                {{-- DESCRIPTION --}}
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
                                        <p class="mt-2 text-sm text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>

                            </div>

                        </div>

                    </section>


                    {{-- CONTACT DETAILS --}}
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

                                {{-- EMAIL --}}
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
                                        <p class="mt-2 text-sm text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                {{-- PHONE --}}
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
                                        <p class="mt-2 text-sm text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                {{-- WEBSITE --}}
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
                                        <p class="mt-2 text-sm text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>

                            </div>

                        </div>

                    </section>


                    {{-- OFFICE ADDRESS --}}
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

                                {{-- ADDRESS LINE 1 --}}
                                <div>

                                    <label class="mb-3 block text-sm font-semibold text-slate-700">
                                        Address Line 1
                                    </label>

                                    <input
                                        wire:model="address_line_1"
                                        type="text"
                                        placeholder="Building, road or street address"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                    >

                                    @error('address_line_1')
                                        <p class="mt-2 text-sm text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                {{-- ADDRESS LINE 2 --}}
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

                                    @error('address_line_2')
                                        <p class="mt-2 text-sm text-red-600">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>


                                <div class="grid grid-cols-1 gap-7 md:grid-cols-2">

                                    {{-- DISTRICT --}}
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

                                        @error('district')
                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                    </div>


                                    {{-- PROVINCE --}}
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

                                        @error('province')
                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                    </div>


                                    {{-- POSTCODE --}}
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

                                        @error('postcode')
                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                    </div>


                                    {{-- COUNTRY --}}
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
                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                    </div>

                                </div>

                            </div>

                        </div>

                    </section>

                </div>


                {{-- RIGHT COLUMN --}}
                <div class="space-y-8">

                    {{-- ACCOUNT STATUS --}}
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-6 py-6">

                            <h4 class="font-semibold text-slate-900">
                                Account Status
                            </h4>

                            <p class="mt-2 text-sm text-slate-500">
                                Control exchange access and verification.
                            </p>

                        </div>

                        <div class="space-y-7 p-6">

                            {{-- STATUS --}}
                            <div>

                                <label class="mb-3 block text-sm font-semibold text-slate-700">
                                    Status
                                </label>

                                <select
                                    wire:model="status"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                >
                                    <option value="pending">
                                        Pending
                                    </option>

                                    <option value="active">
                                        Active
                                    </option>

                                    <option value="suspended">
                                        Suspended
                                    </option>

                                    <option value="disabled">
                                        Disabled
                                    </option>
                                </select>

                                @error('status')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>


                            {{-- VERIFIED --}}
                            <div class="border-t border-slate-100 pt-6">

                                <label
                                    class="flex cursor-pointer items-start gap-4 rounded-xl border border-slate-200 bg-slate-50 p-5 transition hover:border-slate-300"
                                >

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
                                            Confirms that ThaiPropertyX has reviewed
                                            and verified this agency.
                                        </span>

                                    </span>

                                </label>

                            </div>

                        </div>

                    </section>


                    {{-- AGENCY LOGIN --}}
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-base font-bold text-slate-900">
                                Agency Login
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Manage the primary Agency Administrator login.
                            </p>
                        </div>

                        <div class="p-6">

                            @if (session('loginSuccess'))
                                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                                    {{ session('loginSuccess') }}
                                </div>
                            @endif

                            @if ($agencyAdminUserId)


                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            Administrator Name
                                        </label>

                                        <input
                                            type="text"
                                            wire:model="loginName"
                                            autocomplete="off"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('loginName')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>


                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            Login Email
                                        </label>

                                        <input
                                            type="email"
                                            wire:model="loginEmail"
                                            autocomplete="off"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('loginEmail')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>


                                    <div class="border-t border-slate-200 pt-5">

                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            Account Status
                                        </label>

                                        <select
                                            wire:model.live="loginStatus"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >
                                            <option value="active">Active</option>
                                            <option value="suspended">Suspended</option>
                                            <option value="disabled">Disabled</option>
                                        </select>

                                        @error('loginStatus')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                        <p class="mt-2 text-xs leading-5 text-slate-500">
                                            Controls whether this administrator can use their TPX account.
                                        </p>

                                    </div>


                                    <div class="border-t border-slate-200 pt-5">

                                        <h4 class="text-sm font-bold text-slate-900">
                                            Reset Password
                                        </h4>

                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            Leave both fields blank to keep the existing password.
                                        </p>

                                    </div>


                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            New Password
                                        </label>

                                        <input
                                            type="password"
                                            wire:model.live="loginPassword"
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('loginPassword')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>


                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            Confirm New Password
                                        </label>

                                        <input
                                            type="password"
                                            wire:model.live="loginPasswordConfirmation"
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('loginPasswordConfirmation')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>


                                    <button
                                        type="button"
                                        wire:click="saveAgencyLogin"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800"
                                    >
                                        Update Login
                                    </button>


                            @else

                                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-semibold text-amber-800">
                                        No Agency Administrator account
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-amber-700">
                                        Create the primary administrator login for this agency.
                                    </p>
                                </div>

                                <div class="space-y-5">

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            Administrator Name
                                        </label>

                                        <input
                                            type="text"
                                            wire:model="loginName"
                                            autocomplete="off"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('loginName')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            Login Email
                                        </label>

                                        <input
                                            type="email"
                                            wire:model="loginEmail"
                                            autocomplete="off"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('loginEmail')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            Password
                                        </label>

                                        <input
                                            type="password"
                                            wire:model="loginPassword"
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('loginPassword')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                                            Confirm Password
                                        </label>

                                        <input
                                            type="password"
                                            wire:model="loginPasswordConfirmation"
                                            autocomplete="new-password"
                                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                        >

                                        @error('loginPasswordConfirmation')
                                            <p class="mt-2 text-xs font-medium text-red-600">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="createAgencyAdmin"
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800"
                                    >
                                        Create Agency Administrator
                                    </button>

                                </div>
                            @endif

                        </div>

                    </section>

                    {{-- RESOURCE USAGE --}}
                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-base font-bold text-slate-900">
                                Resource Usage
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Storage, media and exchange resources used by this agency.
                            </p>
                        </div>

                        <div class="p-6">

                            @php
                                if ($storageBytes >= 1073741824) {
                                    $storageDisplay = number_format(
                                        $storageBytes / 1073741824,
                                        2
                                    ) . ' GB';
                                } elseif ($storageBytes >= 1048576) {
                                    $storageDisplay = number_format(
                                        $storageBytes / 1048576,
                                        2
                                    ) . ' MB';
                                } elseif ($storageBytes >= 1024) {
                                    $storageDisplay = number_format(
                                        $storageBytes / 1024,
                                        1
                                    ) . ' KB';
                                } else {
                                    $storageDisplay = $storageBytes . ' B';
                                }
                            @endphp

                            <div class="grid grid-cols-2 gap-3">

                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs font-medium text-slate-500">
                                        TPX Storage
                                    </p>
                                    <p class="mt-2 text-lg font-bold text-slate-900">
                                        {{ $storageDisplay }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs font-medium text-slate-500">
                                        TPX Media
                                    </p>
                                    <p class="mt-2 text-lg font-bold text-slate-900">
                                        {{ number_format($tpxMediaCount) }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs font-medium text-slate-500">
                                        External Media
                                    </p>
                                    <p class="mt-2 text-lg font-bold text-slate-900">
                                        {{ number_format($externalMediaCount) }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-slate-50 p-4">
                                    <p class="text-xs font-medium text-slate-500">
                                        Active API Connectors
                                    </p>
                                    <p class="mt-2 text-lg font-bold text-slate-900">
                                        {{ number_format($activeApiConnectorCount) }}
                                    </p>
                                </div>

                            </div>

                            <div class="mt-5 border-t border-slate-200 pt-5">

                                <div class="flex items-center justify-between py-2">
                                    <span class="text-sm text-slate-500">
                                        Active Incoming Syndications
                                    </span>

                                    <span class="text-sm font-bold text-slate-900">
                                        {{ number_format($activeIncomingSyndicationCount) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between py-2">
                                    <span class="text-sm text-slate-500">
                                        Active Outgoing Syndications
                                    </span>

                                    <span class="text-sm font-bold text-slate-900">
                                        {{ number_format($activeOutgoingSyndicationCount) }}
                                    </span>
                                </div>

                            </div>

                        </div>

                    </section>

                    {{-- AGENCY SUMMARY --}}
                    <section class="rounded-2xl bg-slate-950 p-7 text-white shadow-sm">

                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">
                            ThaiPropertyX
                        </p>

                        <h4 class="mt-4 text-xl font-bold">
                            Agency Account
                        </h4>

                        <p class="mt-4 text-sm leading-7 text-slate-300">
                            Changes made here affect the agency's central
                            ThaiPropertyX account and its participation in
                            the property exchange.
                        </p>


                        <div class="mt-7 space-y-5 border-t border-slate-800 pt-6">

                            <div>

                                <p class="text-xs uppercase tracking-wider text-slate-500">
                                    Agency ID
                                </p>

                                <p class="mt-2 text-sm font-semibold text-amber-400">
                                    #{{ $agency->id }}
                                </p>

                            </div>


                            <div>

                                <p class="text-xs uppercase tracking-wider text-slate-500">
                                    Current Status
                                </p>

                                <p class="mt-2 text-sm font-semibold text-amber-400">
                                    {{ ucfirst($status) }}
                                </p>

                            </div>


                            <div>

                                <p class="text-xs uppercase tracking-wider text-slate-500">
                                    Listings
                                </p>

                                <p class="mt-2 text-sm font-semibold text-amber-400">
                                    {{ $agency->properties()->count() }}
                                </p>

                            </div>


                            <div>

                                <p class="text-xs uppercase tracking-wider text-slate-500">
                                    Agents
                                </p>

                                <p class="mt-2 text-sm font-semibold text-amber-400">
                                    {{ $agency->users()->count() }}
                                </p>

                            </div>

                        </div>

                    </section>

                </div>

            </div>


            {{-- ACTION BAR --}}
            <div class="mt-8 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white px-7 py-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <p class="text-sm font-semibold text-slate-900">
                        Save agency changes
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Changes will take effect immediately.
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

                        <span
                            wire:loading.remove
                            wire:target="save"
                        >
                            Update Agency
                        </span>

                        <span
                            wire:loading
                            wire:target="save"
                        >
                            Updating...
                        </span>

                    </button>

                </div>

            </div>

        </form>

    </div>

        {{-- DANGER ZONE --}}
        <section class="mt-8 rounded-2xl border border-red-200 bg-red-50 shadow-sm">

            <div class="border-b border-red-200 px-7 py-6">
                <p class="text-xs font-bold uppercase tracking-wider text-red-600">
                    Danger Zone
                </p>

                <h3 class="mt-2 text-lg font-bold text-red-900">
                    Delete Agency
                </h3>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-red-700">
                    Permanently delete {{ $agency->name }} and its associated
                    agency users, properties, syndications, API connections
                    and agency-specific mapping data.
                </p>
            </div>

            <div class="flex flex-col gap-4 px-7 py-6 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <p class="text-sm font-semibold text-red-900">
                        This action cannot be undone.
                    </p>

                    <p class="mt-1 text-xs leading-5 text-red-700">
                        Only use this when the agency account and its data
                        should be permanently removed from ThaiPropertyX.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="deleteAgency"
                    wire:confirm="Permanently delete {{ $agency->name }} and all associated data? This action cannot be undone."
                    wire:loading.attr="disabled"
                    wire:target="deleteAgency"
                    style="background-color:#dc2626;color:#ffffff;" class="inline-flex shrink-0 items-center justify-center rounded-xl px-5 py-3 text-sm font-bold transition hover:opacity-90 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="deleteAgency">
                        Delete Agency
                    </span>

                    <span wire:loading wire:target="deleteAgency">
                        Deleting...
                    </span>
                </button>

            </div>

        </section>
</x-admin-ui.layout>




