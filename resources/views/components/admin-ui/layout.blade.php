<div class="min-h-screen bg-slate-50">
    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside class="w-72 bg-slate-950 text-white flex flex-col">

            <div class="px-7 py-7 border-b border-slate-800">
                <div class="flex items-center gap-3">

                    <div class="w-11 h-11 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center font-black text-lg">
                        TPX
                    </div>

                    <div>
                        <h1 class="font-bold text-lg leading-tight">
                            ThaiPropertyX
                        </h1>

                        <p class="text-xs text-slate-400 mt-1">
                            Property Exchange
                        </p>
                    </div>

                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2">

                <a
                    href="/admin"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl
                    {{ request()->is('admin') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-12h8V3h-8v6Z"/>
                    </svg>

                    Dashboard
                </a>

                <a
                    href="/admin/agencies"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl
                    {{ request()->is('admin/agencies*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                    </svg>

                    Agencies
                </a>

                <a
                    href="/admin/properties"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl
                    {{ request()->is('admin/properties*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9Z"/>
                    </svg>

                    Properties
                </a>

                <a
                    href="{{ route('admin.exchange') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl
                    {{ request()->is('admin/exchange*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h11l-3-3m3 3-3 3M17 17H6l3 3m-3-3 3-3"/>
                    </svg>

                    TPX Exchange
                </a>

                <a
                    href="/admin/users"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl
                    {{ request()->is('admin/users*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>

                    Users & Agents
                </a>

                <a
                    href="/admin/syndications"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl
                    {{ request()->is('admin/syndications*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h11l-3-3m3 3-3 3M16 17H5l3 3m-3-3 3-3"/>
                    </svg>

                    Syndication
                </a>

                <div class="pt-5 mt-5 border-t border-slate-800">

                    <p class="px-4 mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        Platform
                    </p>

                    <a
                        href="/admin/api"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl
                        {{ request()->is('admin/api*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8M8 15h8M5 5h14v14H5z"/>
                        </svg>

                        API & Feeds
                    </a>

                    <a
                        href="/admin/settings"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl
                        {{ request()->is('admin/settings*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5M19 12a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                        </svg>

                        Settings
                    </a>

                </div>

            </nav>

            <div class="p-4 border-t border-slate-800">
                <div class="rounded-xl bg-slate-900 p-4">

                    <p class="text-sm font-semibold">
                        {{ auth()->user()->name }}
                    </p>

                    <p class="text-xs text-slate-400 mt-1">
                        Platform Administrator
                    </p>

                    <button
                        wire:click="logout"
                        class="mt-4 w-full rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 hover:text-white transition"
                    >
                        Sign out
                    </button>

                </div>
            </div>

        </aside>

        {{-- Main area --}}
        <div class="flex-1 min-w-0">

            <header class="bg-white border-b border-slate-200">
                <div class="px-8 py-5 flex items-center justify-between">

                    <div>
                        <p class="text-sm text-slate-500">
                            ThaiPropertyX Administration
                        </p>

                        <h2 class="text-2xl font-bold text-slate-900 mt-1">
                            {{ $title ?? 'Administration' }}
                        </h2>
                    </div>

                    <div class="flex items-center gap-3">

                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>

                        <div class="hidden lg:block">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ auth()->user()->name }}
                            </p>

                            <p class="text-xs text-slate-500">
                                Platform Admin
                            </p>
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