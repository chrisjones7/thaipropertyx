<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
            'status' => 'active',
        ], $this->remember)) {
            $this->addError('email', 'The email address or password is incorrect.');
            return;
        }

        request()->session()->regenerate();

        $user = Auth::user();

        if (! $user instanceof User || ! $user->isActive()) {
            Auth::logout();

            $this->addError('email', 'This account is not active.');
            return;
        }

        $this->redirect($user->isPlatformAdmin() ? '/admin' : '/dashboard', navigate: true);
    }
};
?>

<div class="min-h-screen bg-slate-100 lg:grid lg:grid-cols-2">
    <!-- TPX branding panel -->
    <section class="relative isolate flex min-h-[280px] flex-col justify-between overflow-hidden bg-[#071A38] p-8 text-white sm:p-12 lg:min-h-screen lg:p-16">
        <div class="absolute inset-0 -z-10 bg-gradient-to-br from-[#071A38] via-[#102F60] to-[#164C94]"></div>
        <div class="absolute -right-32 top-1/3 -z-10 h-96 w-96 rounded-full bg-blue-400/10 blur-3xl"></div>

        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-950/30">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="5" cy="12" r="2"/>
                    <circle cx="19" cy="5" r="2"/>
                    <circle cx="19" cy="19" r="2"/>
                    <path d="M7 11 17 6M7 13l10 5M19 7v10"/>
                </svg>
            </div>
            <div>
                <div class="text-2xl font-bold tracking-tight">ThaiPropertyX</div>
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">The TPX Property Network</div>
            </div>
        </div>

        <div class="my-12 max-w-xl lg:my-16">
            <div class="mb-5 inline-flex rounded-full border border-blue-300/25 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-blue-100">
                Connect. Share. Grow.
            </div>
            <h1 class="text-4xl font-bold leading-tight tracking-tight sm:text-5xl xl:text-6xl">
                One Network.<br>
                More Properties.<br>
                <span class="text-blue-300">More Opportunities.</span>
            </h1>
            <p class="mt-6 max-w-lg text-base leading-8 text-slate-200 sm:text-lg">
                Connect agencies, share listings and expand your reach across Thailand through one property exchange network.
            </p>

            <div class="relative mt-10 overflow-hidden rounded-2xl border border-white/15 bg-[#0C2A53] shadow-2xl shadow-blue-950/30">
                <img
                    src="/images/tpx-login-villa.png"
                    alt="Modern city buildings"
                    class="h-48 w-full object-cover opacity-80 sm:h-60 xl:h-72"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-[#071A38]/80 to-transparent"></div>
                <div class="absolute bottom-5 left-6 text-sm font-medium text-white">
                    A connected future for property professionals
                </div>
            </div>
        </div>

        <div class="text-xs text-blue-100/70">
            &copy; {{ date('Y') }} ThaiPropertyX. All rights reserved.
        </div>
    </section>

    <!-- Login panel -->
    <main class="flex min-h-[620px] items-center justify-center px-5 py-12 sm:px-10 lg:min-h-screen lg:px-12">
        <div class="w-full max-w-md">
            <div class="mb-9">
                <div class="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Member access</div>
                <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Welcome back</h2>
                <p class="mt-3 text-slate-500">Sign in to access your TPX Network dashboard.</p>
            </div>

            <form wire:submit="login" class="space-y-5">
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
                    <input id="email" type="email" wire:model="email" autocomplete="email" autofocus required
                        placeholder="you@agency.com"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <input id="password" type="password" wire:model="password" autocomplete="current-password" required
                        placeholder="Enter your password"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Remember me
                    </label>
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="login"
                    class="flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-4 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:opacity-60">
                    <span wire:loading.remove wire:target="login">Sign In to TPX</span>
                    <span wire:loading wire:target="login">Signing in...</span>
                </button>
            </form>

            <div class="my-9 flex items-center gap-4">
                <div class="h-px flex-1 bg-slate-200"></div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">New to TPX?</span>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>

            <a href="/join"
                class="flex w-full items-center justify-center rounded-xl border-2 border-blue-600 bg-white px-5 py-3.5 text-sm font-bold text-blue-700 transition hover:bg-blue-50">
                Join the TPX Network
            </a>
            <p class="mt-4 text-center text-sm leading-6 text-slate-500">
                Apply for membership and connect your real estate business to the TPX Network.
            </p>

            <p class="mt-12 text-center text-xs text-slate-400">Secure access for approved TPX members</p>
        </div>
    </main>
</div>