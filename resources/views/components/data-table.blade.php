{{--
    Generic data table wrapper.
    Props: none
    Slots:
      $header – <th> cells rendered inside <thead><tr>
      $slot   – <tr> rows rendered inside <tbody>
--}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-neutral-50">
            <tr>
                {{ $header }}
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            {{ $slot }}
        </tbody>
    </table>
</div>
