<!DOCTYPE html>
<html>
<head>
    <title>Invoice for Order #{{ $order->slug }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<div class="header">
    <h1>Invoice</h1>
    <h3>Order #{{ $order->slug }}</h3>
    <p>Date: {{ $order->created_at->format('Y-m-d') }}</p>
</div>

<div class="section">
    <h3>Billing Information</h3>
    <ul>
        <li>Name: {{ $order->name }}</li>
        <li>Email: {{ $order->email }}</li>
        <li>Phone: {{ $order->phone }}</li>
    </ul>
</div>

<div class="section">
    <h3>Order Details</h3>
    <table>
        <thead>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price ({{ $order->user_currency }})</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($order->orderItems as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->product_price_user_currency, 2) }}</td>
            </tr>
        @endforeach
        @if ($order->giftBox)
            <tr>
                <td>Gift Box ({{ $order->gift_box_type }})</td>
                <td>1</td>
                <td>{{ number_format($order->gift_box_price_user_currency, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="2"><strong>Total</strong></td>
            <td><strong>{{ number_format($order->price_in_currency, 2) }} {{ $order->user_currency }}</strong></td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>
