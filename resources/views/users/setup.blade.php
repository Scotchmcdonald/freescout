<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    {{ __('Complete Your Account Setup') }}
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    {{ __('Welcome to') }} {{ config('app.name') }}! {{ __('Please complete your profile to get started.') }}
                </p>
            </div>

            <form class="mt-8 space-y-6" method="POST" action="{{ route('user_setup.save', ['hash' => $user->invite_hash]) }}" enctype="multipart/form-data">
                @csrf

                <div class="rounded-md shadow-sm space-y-4">
                    <!-- First Name (Read-only) -->
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700">
                            {{ __('First Name') }}
                        </label>
                        <input type="text" id="first_name" value="{{ $user->first_name }}" disabled
                               class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm">
                    </div>

                    <!-- Last Name (Read-only) -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700">
                            {{ __('Last Name') }}
                        </label>
                        <input type="text" id="last_name" value="{{ $user->last_name }}" disabled
                               class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            {{ __('Email Address') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            {{ __('Password') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password" id="password" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">{{ __('Minimum 8 characters') }}</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            {{ __('Confirm Password') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Job Title -->
                    <div>
                        <label for="job_title" class="block text-sm font-medium text-gray-700">
                            {{ __('Job Title') }}
                        </label>
                        <input type="text" name="job_title" id="job_title" value="{{ old('job_title', $user->job_title) }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('job_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            {{ __('Phone') }}
                        </label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Timezone -->
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700">
                            {{ __('Timezone') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="timezone" id="timezone" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            @php
                                $timezones = timezone_identifiers_list();
                                $selected_timezone = old('timezone', $user->timezone ?? 'UTC');
                            @endphp
                            @foreach ($timezones as $tz)
                                <option value="{{ $tz }}" {{ $tz === $selected_timezone ? 'selected' : '' }}>
                                    {{ $tz }}
                                </option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Time Format -->
                    <div>
                        <label for="time_format" class="block text-sm font-medium text-gray-700">
                            {{ __('Time Format') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="time_format" id="time_format" required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="12" {{ old('time_format', $user->time_format) == 12 ? 'selected' : '' }}>12-hour</option>
                            <option value="24" {{ old('time_format', $user->time_format) == 24 ? 'selected' : '' }}>24-hour</option>
                        </select>
                        @error('time_format')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Photo Upload -->
                    <div>
                        <label for="photo_url" class="block text-sm font-medium text-gray-700">
                            {{ __('Profile Photo') }}
                        </label>
                        <input type="file" name="photo_url" id="photo_url" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-blue-50 file:text-blue-700
                                      hover:file:bg-blue-100">
                        @error('photo_url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">{{ __('JPEG, PNG, JPG or GIF (max 2MB)') }}</p>
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ __('Complete Setup') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
