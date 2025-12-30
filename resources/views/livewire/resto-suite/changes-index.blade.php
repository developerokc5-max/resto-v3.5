<div class="card">
    <h3>Changes</h3>
    <table>
        <thead><tr><th>Time</th><th>Shop</th><th>Item</th><th>Type</th></tr></thead>
        <tbody>
        @foreach($changes as $c)
            <tr>
                <td>{{ $c->created_at }}</td>
                <td><a href="{{ route('restosuite.shop.items', $c->shop_id) }}">{{ $c->shop_name }}</a></td>
                <td><a href="{{ route('restosuite.item.detail', $c->item_id) }}">{{ $c->item_name }}</a></td>
                <td><span class="pill">{{ $c->change_type }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top:12px;">
        {{ $changes->links() }}
    </div>
</div>
