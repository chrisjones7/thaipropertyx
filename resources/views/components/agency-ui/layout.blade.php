<div class="min-h-screen bg-slate-50">
    <div class="flex min-h-screen">

        {{-- Agency Sidebar --}}
        <aside class="w-72 bg-slate-950 text-white flex flex-col">

            {{-- TPX / Agency Branding --}}
            <div class="px-6 py-6 border-b border-slate-800">

                <div class="flex items-center gap-3">

                    @if(auth()->user()->agency?->logo)
                        <div class="w-12 h-12 rounded-xl bg-white p-1 flex items-center justify-center overflow-hidden">
                            <img
                                src="{{ asset('storage/' . auth()->user()->agency->logo) }}"
                                alt="{{ auth()->user()->agency->name }}"
                                class="max-w-full max-h-full object-contain"
                            >
                        </div>
                    @else
                        <div class="w-12 h-12 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center font-black text-sm">
                            TPX
                        </div>
                    @endif

                    <div class="min-w-0">
                        <p class="font-bold text-white truncate">
                            {{ auth()->user()->agency?->name ?? 'ThaiPropertyX' }}
                        </p>

                        <p class="text-xs text-slate-400 mt-1">
                            powered by ThaiPropertyX
                        </p>
                    </div>

                </div>

            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-4 py-6 space-y-2">

                <a
                    href="{{ route('agency.dashboard') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl
                    {{ request()->is('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-12h8V3h-8v6Z"/>
                    </svg>

                    Dashboard
                </a>

                <a
    href="{{ route('agency.properties') }}"
    class="flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('agency.properties') ? 'bg-slate-900 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z"/>
    </svg>

    My Properties
</a>

                <a
                    href="{{ route('agency.property-network') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('agency.property-network') ? 'bg-slate-900 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h11l-3-3m3 3-3 3M17 17H6l3 3m-3-3 3-3"/>
                    </svg>

                    TPX Property Network
                </a>

                <a
                    href="{{ route('agency.received-properties') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('agency.received-properties') ? 'bg-slate-900 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/>
                    </svg>

                    Received Properties
                </a>

                <a
                    href="{{ route('agency.shared-properties') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('agency.shared-properties') ? 'bg-slate-900 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10m0 0-3-3m3 3-3 3M17 17H7m0 0 3-3m-3 3 3 3"/>
                    </svg>

                    Shared Properties
                </a>

                <div class="pt-5 mt-5 border-t border-slate-800">

                    <p class="px-4 mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        My Agency
                    </p>

                    @if(auth()->user()->isAgencyAdmin())
                        <a
                            href="{{ route('agency.team-agents') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('agency.team-agents') ? 'bg-slate-900 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87"/>
                            </svg>

                            Team & Agents
                        </a>

                        <a
                            href="{{ route('agency.agency-directory') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('agency.agency-directory') ? 'bg-slate-900 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V5h14v16M8 8h2m4 0h2M8 12h2m4 0h2M8 16h2m4 0h2"/>
                            </svg>

                            Agency Directory
                        </a>

                        <a
                            href="{{ route('agency.company-profile') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->is('company-profile') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5M19 12a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                            </svg>

                            Company Profile & Branding
                        </a>

                        <a
                            href="{{ route('agency.sharing-terms') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->is('sharing-terms') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20M17 6.5c0-1.38-2.24-2.5-5-2.5S7 5.12 7 6.5 9.24 9 12 9s5 1.12 5 2.5S14.76 14 12 14s-5 1.12-5 2.5S9.24 19 12 19s5-1.12 5-2.5"/>
                            </svg>

                            Sharing Terms
                        </a>

                        <a
                            href="{{ route('agency.connector-api') }}"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-slate-900 hover:text-white"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 15h8M5 5h14v14H5z"/>
                            </svg>

                            Connector & API
                        </a>
                    @endif

                    <a
                        href="{{ route('agency.my-account') }}"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-slate-900 hover:text-white"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 9a7 7 0 0 0-14 0"/>
                        </svg>

                        My Account
                    </a>

                </div>

            </nav>

            {{-- Logged-in User --}}
            <div class="p-4 border-t border-slate-800">

                <div class="rounded-xl bg-slate-900 p-4">

                    <p class="text-sm font-semibold">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="text-xs text-slate-400 mt-1">
                        {{ auth()->user()->isAgencyAdmin() ? 'Agency Administrator' : 'Agent' }}
                    </p>

                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf

                        <button
                            type="submit"
                            class="w-full rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white transition"
                        >
                            Sign out
                        </button>
                    </form>

                </div>

            </div>

        </aside>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0">

            <header class="bg-white border-b border-slate-200">

                <div class="px-8 py-5 flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            ThaiPropertyX Agency Network
                        </p>

                        <h2 class="text-2xl font-bold text-slate-900 mt-1">
                            {{ $title ?? 'Agency Portal' }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-5">

                        @if(auth()->user()->agency?->verified)
                            <span class="hidden md:inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Verified Agency
                            </span>
                        @endif

                        <div class="flex items-center gap-3">

                            <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>

                            <div class="hidden lg:block">
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ auth()->user()->name }}
                                </p>

                                <p class="text-xs text-slate-500">
                                    {{ auth()->user()->isAgencyAdmin() ? 'Agency Administrator' : 'Agent' }}
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </header>

            <main class="p-8">
                {{ $slot }}
            </main>

        </div>

    </div>
</div>



