<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 text-center">Reset Password</h2>
        <p class="text-sm text-gray-600 text-center mt-1">Enter your email to receive a reset link</p>
    </div>

    <div class="mb-4 text-sm text-gray-600 bg-blue-50 border border-blue-200 rounded-lg p-3">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900">
                {{ __('Send Reset Link') }}
            </x-primary-button>
        </div>

        <div class="mt-4 text-center">
            <a class="text-sm text-indigo-600 hover:text-indigo-800 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Back to Sign In') }}
            </a>
        </div>
    </form>
</x-guest-layout>
