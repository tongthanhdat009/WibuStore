<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #17141F; }
  .header { border-bottom: 3px solid #17141F; padding-bottom: 12px; margin-bottom: 16px; }
  .brand { font-size: 22px; font-weight: bold; }
  .brand span { color: #D6284F; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  th { background: #F7F5F0; border: 1px solid #D8D4D0; padding: 6px; font-size: 11px; text-align: left; }
  td { border: 1px solid #D8D4D0; padding: 6px; }
  .right { text-align: right; }
  .total { font-size: 15px; font-weight: bold; color: #D6284F; }
  .muted { color: #6A6572; font-size: 11px; }
</style>
</head>
<body>
  <div class="header">
    <div class="brand">WIBU <span>STORE</span></div>
    <div class="muted">Anime Figure &amp; Collectibles — support@wibustore.vn — 1900 6868</div>
  </div>

  <h2>HÓA ĐƠN BÁN HÀNG {{ $order->order_number }}</h2>
  <p>
    Khách hàng: <strong>{{ $order->customer_name }}</strong> ({{ $order->phone }}, {{ $order->email }})<br/>
    Ngày đặt: {{ optional($order->placed_at)->format('d/m/Y H:i') ?? $order->created_at->format('d/m/Y H:i') }}<br/>
    Trạng thái đơn: {{ $order->order_status }} • Thanh toán: {{ $order->payment_status }}<br/>
    Địa chỉ giao: {{ $address }}
  </p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Sản phẩm</th>
        <th>SKU</th>
        <th class="right">Đơn giá</th>
        <th class="right">SL</th>
        <th class="right">Thành tiền</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($order->items as $i => $item)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $item->product_name_snapshot }}@if($item->variant_label_snapshot) ({{ $item->variant_label_snapshot }})@endif</td>
        <td>{{ $item->sku_snapshot }}</td>
        <td class="right">{{ number_format($item->unit_price_amount, 0, ',', '.') }} ₫</td>
        <td class="right">{{ $item->quantity }}</td>
        <td class="right">{{ number_format($item->line_total_amount, 0, ',', '.') }} ₫</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table style="margin-top: 8px;">
    <tr><td>Tạm tính</td><td class="right">{{ number_format($order->subtotal_amount, 0, ',', '.') }} ₫</td></tr>
    <tr><td>Giảm giá</td><td class="right">-{{ number_format($order->discount_amount, 0, ',', '.') }} ₫</td></tr>
    <tr><td>Phí vận chuyển</td><td class="right">{{ number_format($order->shipping_amount, 0, ',', '.') }} ₫</td></tr>
    <tr><td><strong>TỔNG THANH TOÁN</strong></td><td class="right total">{{ number_format($order->total_amount, 0, ',', '.') }} ₫</td></tr>
    <tr><td>Đã thanh toán</td><td class="right">{{ number_format($order->paid_amount, 0, ',', '.') }} ₫</td></tr>
  </table>

  <p class="muted">Cảm ơn quý khách đã mua sắm tại Wibu Store. Hóa đơn được tạo tự động lúc {{ now()->format('d/m/Y H:i') }}.</p>
</body>
</html>
