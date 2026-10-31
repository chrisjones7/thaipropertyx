<?php

use App\Models\MembershipEnquiry;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $company_name = '';
    public string $contact_name = '';
    public string $email = '';
    public string $phone = '';
    public string $business_type = '';
    public string $website_url = '';
    public string $website_platform = '';
    public string $wordpress_theme = '';
    public string $location = '';
    public string $message = '';
    public bool $privacy_consent = false;
    public string $website_check = '';
    public bool $submitted = false;

    public function submit(): void
    {
        if ($this->website_check !== '') {
            $this->submitted = true;
            return;
        }

        $this->website_url = trim($this->website_url);

        if ($this->website_url !== '' && ! preg_match('~^https?://~i', $this->website_url)) {
            $this->website_url = 'https://' . $this->website_url;
        }

        $data = $this->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'business_type' => ['required', Rule::in(['agency', 'developer', 'both'])],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'website_platform' => ['required', Rule::in(['wordpress', 'custom', 'none', 'other'])],
            'wordpress_theme' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
            'privacy_consent' => ['accepted'],
        ]);

        if (MembershipEnquiry::where('email', $this->email)->exists()) {
            $this->addError('email', 'An enquiry has already been received from this email address. Please contact TPX if you need to update it.');
            return;
        }

        unset($data['privacy_consent']);

        if ($this->website_platform !== 'wordpress') {
            $data['wordpress_theme'] = null;
        }

        $enquiry = MembershipEnquiry::create($data);

        try {
            $recipients = array_filter(array_map(
                'trim',
                explode(',', config('tpx.membership_notification_emails', ''))
            ));

            if (! empty($recipients)) {
                \Illuminate\Support\Facades\Mail::to($recipients)
                    ->send(new \App\Mail\MembershipEnquiryReceived($enquiry));
            }
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error(
                'TPX membership enquiry notification failed',
                [
                    'enquiry_id' => $enquiry->id,
                    'error' => $exception->getMessage(),
                ]
            );
        }

        $this->submitted = true;
    }
};
?>

<div class="min-h-screen bg-slate-100">
    <div class="bg-[#071A38] text-white">
        <div class="mx-auto max-w-6xl px-6 py-8 sm:px-10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-2xl font-bold tracking-tight">ThaiPropertyX</div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">The TPX Property Network</div>
                </div>
                <a href="/login" class="rounded-lg border border-white/30 px-5 py-2 text-sm font-semibold hover:bg-white/10">Member Sign In</a>
            </div>
            <div class="max-w-3xl py-12 sm:py-16">
                <div class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-blue-300">Membership Enquiry</div>
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Join the TPX Network</h1>
                <p class="mt-5 text-lg leading-8 text-blue-100">Connect your real estate business with a growing property network across Thailand. Tell us about your agency or development company and our team will be in touch.</p>
            </div>
        </div>
    </div>

    <main class="mx-auto max-w-4xl px-5 py-10 sm:px-8 sm:py-14">
        @if ($submitted)
            <div class="rounded-2xl border border-green-200 bg-white p-8 shadow-sm sm:p-12">
                <div class="text-3xl font-bold text-slate-900">Thank you for your interest.</div>
                <p class="mt-4 leading-7 text-slate-600">Your membership enquiry has been received. The TPX team will review your details and contact you about joining the network.</p>
                <a href="/login" class="mt-7 inline-block rounded-lg bg-blue-700 px-6 py-3 font-semibold text-white hover:bg-blue-800">Return to Sign In</a>
            </div>
        @else
            <form wire:submit="submit" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
                <h2 class="text-2xl font-bold text-slate-900">Business Information</h2>
                <p class="mt-2 text-sm text-slate-500">Fields marked * are required.</p>

                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Company / Agency Name *</label>
                        <input wire:model="company_name" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-600 focus:outline-none" placeholder="Your business name">
                        @error('company_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Contact Person *</label>
                        <input wire:model="contact_name" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-600 focus:outline-none">
                        @error('contact_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Email Address *</label>
                        <input wire:model="email" type="email" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-600 focus:outline-none">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Phone / WhatsApp *</label>
                        <input wire:model="phone" type="tel" class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-600 focus:outline-none">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Business Type *</label>
                        <select wire:model="business_type" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3">
                            <option value="">Select business type</option>
                            <option value="agency">Real Estate Agency</option>
                            <option value="developer">Property Developer</option>
                            <option value="both">Agency & Developer</option>
                        </select>
                        @error('business_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Location / Province *</label>
                        <input wire:model="location" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-3" placeholder="e.g. Hua Hin, Prachuap Khiri Khan">
                        @error('location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Website URL</label>
                        <input wire:model="website_url" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-3" placeholder="https://example.com">
                        @error('website_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Website Platform *</label>
                        <select wire:model.live="website_platform" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3">
                            <option value="">Select platform</option>
                            <option value="wordpress">WordPress</option>
                            <option value="custom">Custom Website / CRM</option>
                            <option value="none">No Website</option>
                            <option value="other">Other</option>
                        </select>
                        @error('website_platform') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($website_platform === 'wordpress')
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-semibold text-slate-700">WordPress Theme Name (if known)</label>
                            <input wire:model="wordpress_theme" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-3" placeholder="e.g. Houzez, RealHomes, WP Residence">
                            @error('wordpress_theme') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Additional Information</label>
                        <textarea wire:model="message" rows="4" class="w-full rounded-lg border border-slate-300 px-4 py-3" placeholder="Tell us about your business or what you would like to know about TPX."></textarea>
                        @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="absolute -left-[9999px]" aria-hidden="true">
                    <label>Leave this field empty <input wire:model="website_check" type="text" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="mt-8">
                    <label class="flex items-start gap-3 text-sm leading-6 text-slate-600">
                        <input wire:model="privacy_consent" type="checkbox" class="mt-1 rounded border-slate-300">
                        <span>I agree that ThaiPropertyX may store my submitted information and contact me regarding this membership enquiry. *</span>
                    </label>
                    @error('privacy_consent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="mt-8 w-full rounded-lg bg-blue-700 px-6 py-4 font-semibold text-white shadow-sm hover:bg-blue-800 disabled:opacity-60">
                    <span wire:loading.remove wire:target="submit">Submit Membership Enquiry</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </button>

                <p class="mt-5 text-center text-xs leading-5 text-slate-500">Submitting an enquiry does not automatically create a TPX account. Our team will review your application.</p>
            </form>
        @endif
    </main>
</div>