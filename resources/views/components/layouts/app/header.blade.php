<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="ml-2 mr-5 flex items-center space-x-2 lg:ml-0" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Начало') }}</flux:navbar.item>
                <flux:navbar.item icon="list-bullet" :href="route('meters.list')" :current="request()->routeIs('meters.list')" wire:navigate>{{ __('Водомери') }}</flux:navbar.item>

                <flux:dropdown>
                    <flux:navbar.item icon-trailing="chevron-down" :current="request()->routeIs('readings.multiple') || request()->routeIs('readings.bulk-upload')" wire:navigate>
                        {{ __('Самоотчет') }}
                    </flux:navbar.item>

                    <flux:navmenu>
                        <flux:navmenu.item icon="camera" :href="route('readings.bulk-upload')" wire:navigate>
                            {{ __('Отчет със снимки') }}
                            <flux:badge color="blue" size="sm" class="ml-2" variant="solid">AI</flux:badge>
                        </flux:navmenu.item>
                        <flux:navmenu.item icon="document-text" :href="route('readings.multiple')" wire:navigate>
                            {{ __('Стандартен отчет') }}
                        </flux:navmenu.item>
                    </flux:navmenu>
                </flux:dropdown>

                <flux:navbar.item icon="clipboard-document-list" :href="route('readings.history')" :current="request()->routeIs('readings.history')" wire:navigate>{{ __('История') }}</flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <!-- Report Bug Button -->
                @livewire('report-bug')

            <!-- Desktop User Menu -->
            <flux:dropdown position="top" align="end">
                <flux:profile
                    class="cursor-pointer"
                    :initials="auth()->user()->initials()"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ml-1 flex items-center space-x-2" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Меню">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Начало') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="list-bullet" :href="route('meters.list')" :current="request()->routeIs('meters.list')" wire:navigate>
                    {{ __('Водомери') }}
                    </flux:navlist.item>

                    <!-- Readings Submenu -->
                    <flux:navlist.group
                        expandable
                        :heading="__('Самоотчет')"
                        :icon="request()->routeIs('readings.multiple') || request()->routeIs('readings.bulk-upload') ? 'document-text' : 'document-text'"
                        :current="request()->routeIs('readings.multiple') || request()->routeIs('readings.bulk-upload')"
                    >
                        <flux:navlist.item icon="document-text" :href="route('readings.multiple')" :current="request()->routeIs('readings.multiple')" wire:navigate>
                            {{ __('Стандартен отчет') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="camera" :href="route('readings.bulk-upload')" :current="request()->routeIs('readings.bulk-upload')" wire:navigate>
                            {{ __('Отчет със снимки') }}
                        </flux:navlist.item>
                    </flux:navlist.group>

                    <flux:navlist.item icon="clipboard-document-list" :href="route('readings.history')" :current="request()->routeIs('readings.history')" wire:navigate>
                    {{ __('История') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <!-- Mobile Bug Report Button -->
            <div class="px-3 py-2">
                @livewire('report-bug')
            </div>

            <!-- Mobile Admin Link (if user is admin) -->
            @if(auth()->user()->hasRole('admin'))
            <div class="px-3 py-2">
                <a href="{{ url('/admin') }}" class="inline-flex w-full items-center justify-center rounded-md bg-blue-50 px-3 py-2 text-sm font-medium text-blue-600 ring-1 ring-inset ring-blue-500/10 hover:bg-blue-100 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30 dark:hover:bg-blue-400/20" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mr-1.5 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 4.5a2.25 2.25 0 0 0-2.25 2.25v.5A2.25 2.25 0 0 0 12 9.5a2.25 2.25 0 0 0 2.25-2.25v-.5A2.25 2.25 0 0 0 12 4.5ZM15.75 8.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5h-3.75ZM15.75 14.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5h-3.75ZM4.5 8.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H4.5ZM4.5 14.25a.75.75 0 0 0 0 1.5h3.75a.75.75 0 0 0 0-1.5H4.5Z" />
                        <path d="M17.25 12.75v-.75a2.25 2.25 0 0 0-2.25-2.25h-6a2.25 2.25 0 0 0-2.25 2.25v.75a2.25 2.25 0 0 0 2.25 2.25h6a2.25 2.25 0 0 0 2.25-2.25Z" />
                    </svg>
                    {{ __('Администрация') }}
                </a>
            </div>
            @endif

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Профил">
                    <flux:navlist.item icon="cog" href="/settings/profile" wire:navigate>
                    {{ __('Settings') }}
                    </flux:navlist.item>
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:navlist.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:navlist.item>
                    </form>
                </flux:navlist.group>
            </flux:navlist>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
