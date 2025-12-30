<div class="card">
    <h3>Shops</h3>
    <table>
        <thead><tr><th>Shop</th><th>Brand</th><th>Org</th><th>Items</th></tr></thead>
        <tbody>
        @foreach($shops as $s)
            <tr>
                <td><a href="{{ route('restosuite.shop.items', $s->shop_id) }}">{{ $s->shop_name }}</a></td>
                <td>{{ $s->brand_name }}</td>
                <td>{{ $s->org_code }}</td>
                <td>{{ $s->items_count }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
