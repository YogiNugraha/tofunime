<div class="max-w-md mx-auto my-12 sm:my-16 px-4">
    <div class="bg-surface rounded-2xl border border-stone-warm shadow-sm p-8 sm:p-10">
        <!-- Brand / Header -->
        <div class="text-center mb-8">
            <a href="{{ url('/') }}" class="inline-block mb-3 transition-transform hover:scale-105 duration-150">
                <img
                    src="{{ asset('assets/logo-tofunime.png') }}"
                    alt="Tofunime Logo"
                    class="h-14 w-auto mx-auto object-contain"
                />
            </a>
            <h1 class="font-serif font-semibold text-2xl text-ink tracking-tight">
                Masuk ke Admin Studio
            </h1>
            <p class="text-sm text-ink-muted mt-2">
                Akses CMS Admin Studio untuk mengelola katalog, episode, dan hybrid scraper engine.
            </p>
        </div>

        <!-- Form -->
        <form wire:submit.prevent="authenticate" class="space-y-5">
            <!-- Email Field -->
            <div>
                <label for="email" class="block text-sm font-semibold text-ink mb-1.5">
                    Alamat Email
                </label>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    placeholder="admin@tofunime.test"
                    required
                    autofocus
                    class="w-full bg-alabaster border border-stone-warm rounded-xl px-4 py-3 text-sm text-ink placeholder-ink-muted/60 focus:bg-surface focus:border-mana focus:ring-1 focus:ring-mana outline-none transition-all"
                >
                @error('email')
                    <p class="text-sm text-red-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Field -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-sm font-semibold text-ink">
                        Kata Sandi
                    </label>
                </div>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    placeholder="••••••••"
                    required
                    class="w-full bg-alabaster border border-stone-warm rounded-xl px-4 py-3 text-sm text-ink placeholder-ink-muted/60 focus:bg-surface focus:border-mana focus:ring-1 focus:ring-mana outline-none transition-all"
                >
                @error('password')
                    <p class="text-sm text-red-600 mt-1.5 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        wire:model="remember"
                        class="w-4 h-4 rounded text-mana border-stone-warm focus:ring-mana/30 bg-alabaster"
                    >
                    <span class="text-sm font-medium text-ink-muted">Ingat sesi saya</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full inline-flex items-center justify-center py-3 px-4 bg-mana hover:bg-mana-hover text-white text-sm font-semibold rounded-xl transition-colors shadow-sm disabled:opacity-50"
            >
                <span wire:loading.remove>Masuk ke Admin Studio</span>
                <span wire:loading class="inline-flex items-center space-x-2">
                    <x-lucide-loader-2 class="animate-spin h-4 w-4 shrink-0 text-white" />
                    <span>Memproses...</span>
                </span>
            </button>
        </form>

        <!-- Demo Account Credentials Helper -->
        <div class="mt-8 pt-6 border-t border-stone-warm/80 bg-alabaster/60 rounded-xl p-4">
            <p class="text-sm font-semibold text-ink mb-1">Kredensial Default:</p>
            <div class="text-sm text-ink-muted space-y-1 font-mono">
                <p>Email: <span class="text-ink font-sans font-medium">admin@tofunime.test</span></p>
                <p>Password: <span class="text-ink font-sans font-medium">password</span></p>
            </div>
        </div>
    </div>
</div>
