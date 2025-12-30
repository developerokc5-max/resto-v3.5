<div class="card">
    <h3>Item Detail</h3>

    @if($latest)
        <p><strong>{{ $latest->item_name }}</strong> (ID: {{ $latest->item_id }})</p>
        <p>Latest Shop: <a href="{{ route('restosuite.shop.items', $latest->shop_id) }}">{{ $latest->shop_name }}</a></p>
        <p>Status: <span class="pill">{{ $latest->status }}</span> | Price: {{ number_format((float)$latest->price, 2) }}</p>
    @else
        <p>No snapshot found.</p>
    @endif
</div>

<div class="card">
    <h3>Change History</h3>
    <table>
        <thead><tr><th>Time</th><th>Shop</th><th>Type</th></tr></thead>
        <tbody>
        @foreach($history as $h)
            <tr>
                <td>{{ $h->created_at }}</td>
                <td><a href="{{ route('restosuite.shop.items', $h->shop_id) }}">{{ $h->shop_name }}</a></td>
                <td><span class="pill">{{ $h->change_type }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
