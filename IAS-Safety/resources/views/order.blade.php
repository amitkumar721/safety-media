@extends('layouts.app')
@section('content')
<style>
#order{
    BACKGROUND: GRAY;
}
</style>

<div class="container-fluid" style="padding: 24px;">
  <table class="table table-bordered data-table" id="table">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/jquery.dataTables.min.css" />
    <thead>
      <tr>
        <th>Order ID</th>
        <th>Full Name</th>
        <th>Company</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Status</th>
		    <th>Payment Method</th>
        <th>Spire Customer No</th>
        <th>Details</th>
      </tr>
    </thead>
    <tbody>
    
@foreach ($orderdata as $order)
<?php //echo "<pre>"; print_r($order); die; ?>
<tr <?php echo  ($order['direct_align_with_spire'])? 'id="order"':''; ?>>
  <td>{{ $order['bigc_orderid'] }}</td>
  <td>{{ isset($order->orderCustomer['first_name']) ?$order->orderCustomer['first_name']: '' }} {{ isset($order->orderCustomer['last_name'])?$order->orderCustomer['last_name']:'' }}</td>  
  <td>{{ isset($order->orderCustomer['company_name'])?$order->orderCustomer['company_name']: '' }}</td>
  <td>{{ isset($order->orderCustomer['email'])?$order->orderCustomer['email']:'' }}</td>
  <td>{{ isset($order->orderCustomer['phone'])?$order->orderCustomer['phone']:'' }}</td>
  <td>{{ $order['status'] }}</td>
  <td>{{ $order['payment_method'] }} </td>
  <td>{{ isset($order->orderCustomer['zoho_customer_no'])?$order->orderCustomer['zoho_customer_no']: '' }}</td>
  <td><a class="btn btn-primary me-2" href="{{URL::to('/order')}}/{{ $order['id'] }}">Details</a></td>
</tr>

@endforeach
</tbody>
</table>

<script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
<!-- <script src="{{ asset('js/app.js') }}" defer></script> -->
<script src="//cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js" defer></script>
<script>
  $(document).ready(function() {
    $('#table').DataTable( {
        "order": [[ 0, "desc" ]]
    } );
  });
</script>
</div>
@endsection