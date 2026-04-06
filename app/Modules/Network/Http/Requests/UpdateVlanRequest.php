<?php

namespace App\Modules\Network\Http\Requests;

use App\Modules\Network\Models\DhcpServer;
use App\Modules\Network\Services\IpGeneratorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateVlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasModulePermission('network', 'edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get the VLAN being updated from the route parameter
        $vlanId = $this->route('vlan')->id;

        return [
            'vlan_id' => ['required', 'integer', 'min:1', 'max:4094', Rule::unique('vlans', 'vlan_id')->ignore($vlanId)],
            'vlan_name' => ['required', 'string', 'max:255'],
            'network_address' => ['required', 'ip'],
            'cidr_suffix' => ['required', 'integer', 'min:0', 'max:32'],
            'gateway' => ['nullable', 'ip'],
            'dhcp_enabled' => ['boolean'],
            'dhcp_from' => ['required_if:dhcp_enabled,1', 'nullable', 'ip'],
            'dhcp_to' => ['required_if:dhcp_enabled,1', 'nullable', 'ip'],
            'dhcp_server_id' => ['nullable', 'integer', 'exists:network_dhcp_servers,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'internes_netz' => ['boolean'],
            'ipscan' => ['boolean'],
            'scan_interval_minutes' => ['integer', 'min:1'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Get the data being validated
            $data = $validator->getData();
            $networkAddress = $data['network_address'] ?? null;
            $cidrSuffix = $data['cidr_suffix'] ?? null;
            $gateway = $data['gateway'] ?? null;
            $dhcpFrom = $data['dhcp_from'] ?? null;
            $dhcpTo = $data['dhcp_to'] ?? null;

            // Skip custom validation if basic validation failed or data is missing
            if ($validator->errors()->has('network_address') || 
                $validator->errors()->has('cidr_suffix') ||
                !$networkAddress || 
                !is_numeric($cidrSuffix)) {
                return;
            }

            // Get subnet information
            $ipGenerator = app(IpGeneratorService::class);
            $subnetInfo = $ipGenerator->calculateSubnetInfo($networkAddress, $cidrSuffix);

            // Validate that network_address and cidr_suffix form a valid subnet
            if ($subnetInfo['network'] === null) {
                $validator->errors()->add('network_address', 'The network address and CIDR suffix do not form a valid subnet.');
                return;
            }

            // Validate gateway is within subnet
            if ($gateway) {
                if (!$this->isIpInSubnet($gateway, $subnetInfo)) {
                    $validator->errors()->add('gateway', 'The gateway address must be within the VLAN subnet.');
                }
            }

            // DHCP-Server Pflicht wenn DHCP aktiviert und mehrere Server vorhanden
            $dhcpEnabled = isset($data['dhcp_enabled']) && $data['dhcp_enabled'];
            if ($dhcpEnabled && DhcpServer::count() > 0 && empty($data['dhcp_server_id'])) {
                $validator->errors()->add('dhcp_server_id', 'Bitte einen DHCP-Server auswählen.');
            }

            // Validate DHCP range
            if ($dhcpFrom && $dhcpTo) {
                // Check if both DHCP addresses are within subnet
                if (!$this->isIpInSubnet($dhcpFrom, $subnetInfo)) {
                    $validator->errors()->add('dhcp_from', 'The DHCP start address must be within the VLAN subnet.');
                }

                if (!$this->isIpInSubnet($dhcpTo, $subnetInfo)) {
                    $validator->errors()->add('dhcp_to', 'The DHCP end address must be within the VLAN subnet.');
                }

                // Check if dhcp_from <= dhcp_to
                $dhcpFromLong = ip2long($dhcpFrom);
                $dhcpToLong = ip2long($dhcpTo);

                if ($dhcpFromLong !== false && $dhcpToLong !== false && $dhcpFromLong > $dhcpToLong) {
                    $validator->errors()->add('dhcp_from', 'The DHCP start address must be less than or equal to the DHCP end address.');
                }
            }
        });
    }

    /**
     * Check if an IP address is within a subnet.
     *
     * @param string $ip The IP address to check
     * @param array $subnetInfo The subnet information from calculateSubnetInfo
     * @return bool
     */
    private function isIpInSubnet(string $ip, array $subnetInfo): bool
    {
        $ipLong = ip2long($ip);
        $networkLong = ip2long($subnetInfo['network']);
        $broadcastLong = ip2long($subnetInfo['broadcast']);

        if ($ipLong === false || $networkLong === false || $broadcastLong === false) {
            return false;
        }

        return $ipLong >= $networkLong && $ipLong <= $broadcastLong;
    }
}
