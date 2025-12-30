<div>
    <div class="grid">
        <div class="card"><div class="pill">Total shops</div><h2>{{ $totalShops }}</h2></div>
        <div class="card"><div class="pill">Total snapshots</div><h2>{{ $totalItems }}</h2></div>
        <div class="card"><div class="pill">Changes (24h)</div><h2>{{ $changes24h }}</h2></div>
    </div>

    <div class="card">
        <h3>Latest changes</h3>
        <table>
            <thead>
            <tr><th>Time</th><th>Shop</th><th>Item</th><th>Type</th></tr>
            </thead>
            <tbody>
            @foreach($latestChanges as $c)
                <tr>
                    <td>{{ $c->created_at }}</td>
                    <td><a href="{{ route('restosuite.shop.items', $c->shop_id) }}">{{ $c->shop_name }}</a></td>
                    <td><a href="{{ route('restosuite.item.detail', $c->item_id) }}">{{ $c->item_name }}</a></td>
                    <td><span class="pill">{{ $c->change_type }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
