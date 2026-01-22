<form method="POST" action="/customers/new" id="customerForm">
    @csrf
    <div class="space-y-6 bg-white p-6 rounded-lg shadow">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name *</label>
                <input type="text" name="first_name" id="first_name" required value="{{ old('first_name', $customer->first_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $customer->last_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Addresses</label>
            @foreach($emails as $index => $email)
                <div class="flex gap-2 mb-2">
                    <input type="email" name="emails[{{ $index }}][email]" value="{{ is_array($email) ? ($email['email'] ?? '') : $email }}" placeholder="email@example.com" class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <select name="emails[{{ $index }}][type]" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="work">Work</option>
                        <option value="home">Home</option>
                    </select>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                {{ $save_button_title ?? 'Save' }}
            </button>
        </div>
    </div>
</form>
