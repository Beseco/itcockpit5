@props(['ipAddress'])

@if($ipAddress->isInDhcpRange())
    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
        DHCP
    </span>
@endif
