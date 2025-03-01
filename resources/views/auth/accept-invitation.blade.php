<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header 
            title="Accept Invitation" 
            description="You've been invited to join Apartment {{ $invitation->apartment->number }}" 
        />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('invitation.process', ['code' => $invitation->code]) }}" class="flex flex-col gap-6">
            @csrf

            <!-- Name -->
            <flux:input
                id="name"
                label="Name"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                autocomplete="name"
                placeholder="Your full name"
            />

            <!-- Email -->
            <flux:input
                id="email"
                label="Email address"
                type="email"
                name="email"
                :value="$invitation->email"
                disabled
                class="bg-gray-100"
            />
            <p class="mt-[-16px] text-sm text-zinc-500">Email cannot be changed</p>

            <!-- Password -->
            <flux:input
                id="password"
                label="Password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Password"
            />

            <!-- Confirm Password -->
            <flux:input
                id="password_confirmation"
                label="Confirm password"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
            />

            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                <p>This invitation expires on: {{ $invitation->expires_at->format('Y-m-d H:i') }}</p>
            </div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    Create Account
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts.auth>