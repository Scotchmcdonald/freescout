<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Crm\Models\Client;
use Modules\WidgetRegistry\Services\WidgetRegistryService;
use Illuminate\View\View;

/**
 * Client 360 View Controller
 * 
 * Aggregates data from multiple modules for admin client overview.
 * Uses WidgetRegistry to maintain module independence (Core Blindness pattern).
 */
class Client360Controller extends Controller
{
    public function show(int $id, WidgetRegistryService $widgetRegistry): View
    {
        $client = Client::findOrFail($id);
        
        $this->authorize('view', $client);

        // Contacts Data (always available from CRM)
        $contacts = $client->contacts()
            ->orderBy('is_primary', 'desc')
            ->orderBy('last_name')
            ->get();

        // Get widgets for different sections
        $assetWidgets = $widgetRegistry->getWidgets('client_360.assets', ['client' => $client])
            ->map(fn($widget) => $widget->render(['client' => $client]));
        $financialWidgets = $widgetRegistry->getWidgets('client_360.financials', ['client' => $client])
            ->map(fn($widget) => $widget->render(['client' => $client]));
        $sidebarWidgets = $widgetRegistry->getWidgets('client_360.sidebar', ['client' => $client])
            ->map(fn($widget) => $widget->render(['client' => $client]));

        return view('admin.clients.show', compact(
            'client',
            'contacts',
            'assetWidgets',
            'financialWidgets',
            'sidebarWidgets'
        ));
    }
}
