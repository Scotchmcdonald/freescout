<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Crm\Models\Client;
use Modules\WidgetRegistry\Services\WidgetRegistryService;

/**
 * Client 360 View Controller
 * 
 * Aggregates data from multiple modules for admin client overview.
 * Uses WidgetRegistry to maintain module independence (Core Blindness pattern).
 */
class Client360Controller extends Controller
{
    public function show($id, WidgetRegistryService $widgetRegistry)
    {
        $client = Client::findOrFail($id);
        
        $this->authorize('view', $client);

        // Contacts Data (always available from CRM)
        $contacts = $client->contacts()
            ->orderBy('is_primary', 'desc')
            ->orderBy('last_name')
            ->get();

        // Helper to get rendered widgets
        $getRenderedWidgets = function($zone) use ($widgetRegistry, $client) {
            return $widgetRegistry->getWidgets($zone, ['client' => $client])
                ->map(fn($widget) => $widget->render(['client' => $client]));
        };

        // Get widgets for different sections
        $assetWidgets = $getRenderedWidgets('client_360.assets');
        $financialWidgets = $getRenderedWidgets('client_360.financials');
        $sidebarWidgets = $getRenderedWidgets('client_360.sidebar');

        return view('admin.clients.show', compact(
            'client',
            'contacts',
            'assetWidgets',
            'financialWidgets',
            'sidebarWidgets'
        ));
    }
}
