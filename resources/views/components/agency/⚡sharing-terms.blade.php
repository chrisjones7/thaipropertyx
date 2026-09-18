<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public $agency;

    public bool $sharing_enabled = true;
    public string $commission_model = 'split';

    public $listing_agency_split = 50;
    public $cooperating_agency_split = 50;
    public $sale_price_commission_percent = null;
    public $fixed_commission_amount = null;

    public string $commission_currency = 'THB';
    public string $vat_treatment = 'excluded';
    public string $commission_payable_when = 'transfer';
    public ?string $sharing_terms = null;

    public function mount(): void
    {
        abort_unless(Auth::check(), 403);

        $user = Auth::user();

        abort_unless($user->isAgencyAdmin(), 403);
        abort_unless($user->agency_id, 403);

        $this->agency = $user->agency;

        abort_unless($this->agency, 403);

        $this->sharing_enabled = (bool) $this->agency->sharing_enabled;
        $this->commission_model = $this->agency->commission_model ?? 'split';

        $this->listing_agency_split = $this->agency->listing_agency_split ?? 50;
        $this->cooperating_agency_split = $this->agency->cooperating_agency_split ?? 50;

        $this->sale_price_commission_percent =
            $this->agency->sale_price_commission_percent;

        $this->fixed_commission_amount =
            $this->agency->fixed_commission_amount;

        $this->commission_currency =
            $this->agency->commission_currency ?? 'THB';

        $this->vat_treatment =
            $this->agency->vat_treatment ?? 'excluded';

        $this->commission_payable_when =
            $this->agency->commission_payable_when ?? 'transfer';

        $this->sharing_terms =
            $this->agency->sharing_terms;
    }

    public function updatedListingAgencySplit($value): void
    {
        if (is_numeric($value) && $value >= 0 && $value <= 100) {
            $this->cooperating_agency_split =
                number_format(100 - (float) $value, 2, '.', '');
        }
    }

    public function updatedCooperatingAgencySplit($value): void
    {
        if (is_numeric($value) && $value >= 0 && $value <= 100) {
            $this->listing_agency_split =
                number_format(100 - (float) $value, 2, '.', '');
        }
    }

    public function save(): void
    {
        $rules = [
            'sharing_enabled' => ['boolean'],

            'commission_model' => [
                'required',
                'in:split,sale_percentage,fixed_fee,custom'
            ],

            'commission_currency' => [
                'required',
                'in:THB,USD,EUR,GBP'
            ],

            'vat_treatment' => [
                'required',
                'in:excluded,included,not_applicable'
            ],

            'commission_payable_when' => [
                'required',
                'in:transfer,completion,custom'
            ],

            'sharing_terms' => [
                'nullable',
                'string',
                'max:5000'
            ],
        ];

        if ($this->commission_model === 'split') {
            $rules['listing_agency_split'] = [
                'required',
                'numeric',
                'min:0',
                'max:100'
            ];

            $rules['cooperating_agency_split'] = [
                'required',
                'numeric',
                'min:0',
                'max:100'
            ];
        }

        if ($this->commission_model === 'sale_percentage') {
            $rules['sale_price_commission_percent'] = [
                'required',
                'numeric',
                'gt:0',
                'max:100'
            ];
        }

        if ($this->commission_model === 'fixed_fee') {
            $rules['fixed_commission_amount'] = [
                'required',
                'numeric',
                'gt:0'
            ];
        }

        $this->validate($rules);

        if ($this->commission_model === 'split') {
            $total =
                (float) $this->listing_agency_split +
                (float) $this->cooperating_agency_split;

            if (abs($total - 100) > 0.01) {
                $this->addError(
                    'listing_agency_split',
                    'The two agency percentages must total 100%.'
                );

                return;
            }
        }

        $this->agency->update([
            'sharing_enabled' => $this->sharing_enabled,

            'commission_model' => $this->commission_model,

            'listing_agency_split' =>
                $this->commission_model === 'split'
                    ? $this->listing_agency_split
                    : null,

            'cooperating_agency_split' =>
                $this->commission_model === 'split'
                    ? $this->cooperating_agency_split
                    : null,

            'sale_price_commission_percent' =>
                $this->commission_model === 'sale_percentage'
                    ? $this->sale_price_commission_percent
                    : null,

            'fixed_commission_amount' =>
                $this->commission_model === 'fixed_fee'
                    ? $this->fixed_commission_amount
                    : null,

            'commission_currency' => $this->commission_currency,

            'vat_treatment' => $this->vat_treatment,

            'commission_payable_when' =>
                $this->commission_payable_when,

            'sharing_terms' => $this->sharing_terms,
        ]);

        session()->flash(
            'success',
            'Default property sharing terms updated successfully.'
        );
    }
};
?>

<div>
<x-agency-ui.layout title="Sharing Terms">

    <div class="max-w-5xl">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">
                Property Sharing Terms
            </h1>

            <p class="mt-2 text-sm text-slate-500 max-w-3xl">
                Set the default commercial terms offered to other TPX agencies
                when they syndicate your properties. Individual properties can
                later use different terms where required.
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 shadow-sm">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">✓</span>
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="save" class="space-y-6">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="flex items-start justify-between gap-6">

                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Property Sharing
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Allow other TPX agencies to cooperate with your agency
                            under your published default terms.
                        </p>
                    </div>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="sharing_enabled"
                            class="h-5 w-5 rounded border-slate-300"
                        >

                        <span class="text-sm font-medium text-slate-700">
                            Enabled
                        </span>
                    </label>

                </div>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-slate-900">
                    Default Commission Model
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Choose how the cooperating agency will normally be compensated.
                </p>

                <div class="mt-6 grid gap-4 md:grid-cols-2">

                    <label class="cursor-pointer rounded-xl border border-slate-200 p-4 hover:border-amber-400">
                        <div class="flex gap-3">
                            <input
                                type="radio"
                                wire:model.live="commission_model"
                                value="split"
                                class="mt-1"
                            >

                            <div>
                                <div class="font-semibold text-slate-900">
                                    Commission Split
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    Split the commission received between both agencies,
                                    for example 50 / 50.
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer rounded-xl border border-slate-200 p-4 hover:border-amber-400">
                        <div class="flex gap-3">
                            <input
                                type="radio"
                                wire:model.live="commission_model"
                                value="sale_percentage"
                                class="mt-1"
                            >

                            <div>
                                <div class="font-semibold text-slate-900">
                                    Percentage of Sale Price
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    Pay the cooperating agency a percentage of the
                                    final property sale price.
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer rounded-xl border border-slate-200 p-4 hover:border-amber-400">
                        <div class="flex gap-3">
                            <input
                                type="radio"
                                wire:model.live="commission_model"
                                value="fixed_fee"
                                class="mt-1"
                            >

                            <div>
                                <div class="font-semibold text-slate-900">
                                    Fixed Fee
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    Pay a fixed co-broker or referral amount when
                                    the transaction completes.
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="cursor-pointer rounded-xl border border-slate-200 p-4 hover:border-amber-400">
                        <div class="flex gap-3">
                            <input
                                type="radio"
                                wire:model.live="commission_model"
                                value="custom"
                                class="mt-1"
                            >

                            <div>
                                <div class="font-semibold text-slate-900">
                                    Contact Agency / Custom
                                </div>

                                <div class="mt-1 text-sm text-slate-500">
                                    No standard amount. Commercial terms are agreed
                                    directly between the agencies.
                                </div>
                            </div>
                        </div>
                    </label>

                </div>

                @error('commission_model')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

            </div>

            @if ($commission_model === 'split')

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h2 class="text-lg font-semibold text-slate-900">
                        Commission Split
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        These percentages represent how the commission actually
                        received is divided between the two agencies.
                    </p>

                    <div class="mt-6 grid gap-6 md:grid-cols-2">

                        <div>
                            <label class="text-sm font-medium text-slate-700">
                                Listing Agency
                            </label>

                            <div class="mt-2 flex">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    wire:model.live.debounce.500ms="listing_agency_split"
                                    class="w-full rounded-l-lg border border-slate-300 px-4 py-3"
                                >

                                <span class="rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-4 py-3">
                                    %
                                </span>
                            </div>

                            @error('listing_agency_split')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">
                                Cooperating Agency
                            </label>

                            <div class="mt-2 flex">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    wire:model.live.debounce.500ms="cooperating_agency_split"
                                    class="w-full rounded-l-lg border border-slate-300 px-4 py-3"
                                >

                                <span class="rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-4 py-3">
                                    %
                                </span>
                            </div>

                            @error('cooperating_agency_split')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                </div>

            @elseif ($commission_model === 'sale_percentage')

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h2 class="text-lg font-semibold text-slate-900">
                        Sale Price Commission
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Percentage of the final sale price payable to the
                        cooperating agency.
                    </p>

                    <div class="mt-6 max-w-sm">
                        <label class="text-sm font-medium text-slate-700">
                            Cooperating Agency Commission
                        </label>

                        <div class="mt-2 flex">
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                max="100"
                                wire:model="sale_price_commission_percent"
                                class="w-full rounded-l-lg border border-slate-300 px-4 py-3"
                            >

                            <span class="rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-4 py-3">
                                %
                            </span>
                        </div>

                        @error('sale_price_commission_percent')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

            @elseif ($commission_model === 'fixed_fee')

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h2 class="text-lg font-semibold text-slate-900">
                        Fixed Commission
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Fixed amount payable to the cooperating agency.
                    </p>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">

                        <div>
                            <label class="text-sm font-medium text-slate-700">
                                Amount
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model="fixed_commission_amount"
                                class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3"
                            >

                            @error('fixed_commission_amount')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">
                                Currency
                            </label>

                            <select
                                wire:model="commission_currency"
                                class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3"
                            >
                                <option value="THB">THB - Thai Baht</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                            </select>
                        </div>

                    </div>

                </div>

            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-slate-900">
                    Payment Conditions
                </h2>

                <div class="mt-6 grid gap-6 md:grid-cols-2">

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            VAT Treatment
                        </label>

                        <select
                            wire:model="vat_treatment"
                            class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3"
                        >
                            <option value="excluded">VAT excluded / added separately</option>
                            <option value="included">VAT included</option>
                            <option value="not_applicable">Not applicable</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Commission Payable
                        </label>

                        <select
                            wire:model="commission_payable_when"
                            class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3"
                        >
                            <option value="transfer">On ownership transfer</option>
                            <option value="completion">On transaction completion</option>
                            <option value="custom">As specified in additional terms</option>
                        </select>
                    </div>

                </div>

            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-slate-900">
                    Additional Terms
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Add any conditions another agency should understand before
                    choosing to syndicate your property.
                </p>

                <textarea
                    wire:model="sharing_terms"
                    rows="6"
                    placeholder="Example: Commission is payable after successful transfer and receipt of the seller's commission. Any special property-specific terms will be shown separately."
                    class="mt-4 w-full rounded-lg border border-slate-300 px-4 py-3"
                ></textarea>

                @error('sharing_terms')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror

            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">

                <div class="font-semibold text-amber-900">
                    Default Terms
                </div>

                <p class="mt-1 text-sm leading-6 text-amber-800">
                    These are your agency's default terms. Later, individual
                    properties can override these defaults. When another agency
                    accepts a property for syndication, TPX will preserve a
                    snapshot of the agreed terms so subsequent changes to your
                    defaults do not alter an existing agreement.
                </p>

            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="rounded-xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    Save Sharing Terms
                </button>
            </div>

        </form>

    </div>

</x-agency-ui.layout>
</div>
