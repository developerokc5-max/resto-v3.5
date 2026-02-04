<div class="card">
    <div style="margin-bottom: 20px;">
        <h3 style="display: inline-block; margin-top: 0;">Items — {{ $shopName }}</h3>
        <div style="float: right;">
            <input type="text" wire:model.debounce-300ms="q" placeholder="Search items..." style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
            @if($itemsOff > 0)
                <span style="margin-left: 10px; color: #dc3545; font-weight: bold;">{{ $itemsOff }} offline</span>
            @endif
        </div>
        <div style="clear: both;"></div>
    </div>
    <table>
        <thead><tr><th>Item</th><th>Status</th><th>Price</th></tr></thead>
        <tbody>
        @forelse($items as $it)
            <tr>
                <td><a href="{{ route('restosuite.item.detail', $it->item_id) }}">{{ $it->item_name }}</a></td>
                <td><span class="pill">{{ $it->status }}</span></td>
                <td>{{ number_format((float)$it->price, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" style="text-align: center; padding: 20px; color: #999;">No items found</td></tr>
        @endforelse
        </tbody>
    </table>

    @if($items->hasPages())
        <div style="margin-top: 20px;">
            {{ $items->links() }}
        </div>
    @endif
</div>
