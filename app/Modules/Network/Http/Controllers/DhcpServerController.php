<?php

namespace App\Modules\Network\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Network\Models\DhcpServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DhcpServerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(DhcpServer::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:network_dhcp_servers,name'],
            'symbol'      => ['required', 'in:' . implode(',', array_keys(DhcpServer::SYMBOLS))],
            'color'       => ['required', 'in:' . implode(',', array_keys(DhcpServer::COLORS))],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $server = DhcpServer::create($data);
        return response()->json($server, 201);
    }

    public function update(Request $request, DhcpServer $dhcpServer): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:network_dhcp_servers,name,' . $dhcpServer->id],
            'symbol'      => ['required', 'in:' . implode(',', array_keys(DhcpServer::SYMBOLS))],
            'color'       => ['required', 'in:' . implode(',', array_keys(DhcpServer::COLORS))],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $dhcpServer->update($data);
        return response()->json($dhcpServer);
    }

    public function destroy(DhcpServer $dhcpServer): JsonResponse
    {
        $dhcpServer->update(['dhcp_server_id' => null]); // detach vlans via nullOnDelete already, but explicit is fine
        $dhcpServer->delete();
        return response()->json(['ok' => true]);
    }
}
