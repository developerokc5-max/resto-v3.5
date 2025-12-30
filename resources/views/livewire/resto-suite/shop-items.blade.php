<div class="card">
    <h3>Items — {{ $shopName }}</h3>
    <table>
        <thead><tr><th>Item</th><th>Status</th><th>Price</th></tr></thead>
        <tbody>
        @foreach($items as $it)
            <tr>
                <td><a href="{{ route('restosuite.item.detail', $it->item_id) }}">{{ $it->item_name }}</a></td>
                <td><span class="pill">{{ $it->status }}</span></td>
                <td>{{ number_format((float)$it->price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
