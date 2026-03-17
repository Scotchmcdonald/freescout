<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl" style="color: var(--theme-text-main)">
            {{ __('Dry-Run Variance Explorer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium text-neutral-900">Projected Invoice Variances</h3>
                        <p class="text-sm text-neutral-500">Comparison of next scheduled invoice vs last generated invoice.</p>
                    </div>
                </div>

                @if(count($variances) === 0)
                    <div class="text-center py-8 text-neutral-500">
                        No active billing templates found.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-neutral-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Client</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Last Month</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Projected</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Variance</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($variances as $row)
                                    <tr class="hover:bg-neutral-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-neutral-900">
                                            {{ $row['client_name'] }}
                                            <div class="text-xs text-neutral-500">{{ $row['template_name'] }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500">
                                            ${{ number_format($row['previous_amount'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-900 font-semibold">
                                            ${{ number_format($row['current_amount'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="{{ $row['percent_change'] > 0 ? 'text-danger-600' : ($row['percent_change'] < 0 ? 'text-success-600' : 'text-neutral-500') }}">
                                                {{ $row['percent_change'] > 0 ? '+' : '' }}{{ $row['percent_change'] }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($row['is_unusual'])
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800">
                                                    Unusual (>20%)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800">
                                                    Normal
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
