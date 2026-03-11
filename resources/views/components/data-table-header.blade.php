{{--
    Column header cell for x-data-table.
    Props: none — content passed via default slot.
--}}
<th {{ $attributes->merge(['class' => 'px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap']) }}>
    {{ $slot }}
</th>
