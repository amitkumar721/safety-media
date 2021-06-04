@extends('layouts.app')
@section('content')
<style>
	h2, h3{
		color: #17a2b8!important;
		font-weight: 500!important;
	}
	.loader {
		position: relative;
		border: 16px solid #f3f3f3;
		border-radius: 50%;
		border-top: 16px solid #3498db;
		width: 70px;
		height: 70px;
		left:50%;
		top:50%;
		-webkit-animation: spin 2s linear infinite; /* Safari */
		animation: spin 2s linear infinite;
	}
	#overlay {
		position: absolute;
		top:0px;
		left:0px;
		width: 100%;
		height: 100%;
		background: black;
		opacity: .5;
		z-index: 999;
		display:none;
	}
	.container {
		position:relative;
		height: 300px;
		width: 200px;
		border:1px solid
	}

	/* Safari */
	@-webkit-keyframes spin {
	0% { -webkit-transform: rotate(0deg); }
	100% { -webkit-transform: rotate(360deg); }
	}

	@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
	}
</style>
<div id="overlay">
    <div class="loader"></div>
</div>
<div class="container-fluid" style="padding: 24px;">
	<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

	<div class="add">
		<a href="{{URL::to('/')}}"><button type="button" class="btn btn-info">Back</button></a>
	</div>
	<h2 style="text-align: center;">Big Commerce Order</h2>
	<div class="cus_info">
		<div class="">
			<h3>Ordering Contact</h3>
			<div class="row">
				<div class="col-md-6">
					<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">Customer Id: </label>
						</div>
						<div class="col-md-8" id="cusId"> {{ $orderDetails->orderCustomer['bigc_customer_id'] }}
						</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">First Name:</label>
						</div>
						<div class="col-md-8">{{ $orderDetails->orderCustomer['first_name'] }}</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">Last Name:</label>
						</div>
						<div class="col-md-8">{{ $orderDetails->orderCustomer['last_name'] }}</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">Company</label>
						</div>
						<div class="col-md-8" id="cuscompany"> {{ $orderDetails->orderCustomer['company_name'] }}</div>
					</div>
				
					<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">Email</label>
						</div>
						<div class="col-md-8"> {{ $orderDetails->orderCustomer['email'] }}</div>
					</div>
				</div>
				<div class="col-md-6">
				<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">Phone</label>
						</div>
						<div class="col-md-8" id="cuscompany"> {{ $orderDetails->orderCustomer['phone'] }}</div>
					</div>
				<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">Address 1</label>
						</div>
						<div class="col-md-8" id="cuscompany"> {{ $orderDetails->orderCustomer['address_line1'] }}</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">Address 2</label>
						</div>
						<div class="col-md-8" id="cuscompany"> {{ $orderDetails->orderCustomer['address_line2'] }}</div>
					</div>
					<!-- <div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-3">Zoho Account Id</label>
						</div>
						<div class="col-md-8" id="cuscompany"> {{ $orderDetails->orderCustomer['zoho_account_id'] }}</div>
					</div>
					<div class="row">
						<div class="col-md-5">
							<label for="nameField" class="col-xs-3">Zoho Account Name</label>
						</div>
						<div class="col-md-8" id="cuscompany"> {{ $orderDetails->orderCustomer['zoho_account_name'] }}</div>
					</div> -->
					<div class="row">
						<div class="col-md-4">
						 @if(!$isCustomerOnSpire)
						    <label for="nameField" class="col-xs-2">Fixed Customer No.</label>					
						 @else
							<label for="nameField" class="col-xs-2">Spire Customer No.</label>
						 @endif
						</div>
						<div class="col-md-8" id="zohoCustomerNo">{{ $orderDetails->orderCustomer['zoho_customer_no'] }}</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<label for="nameField" class="col-xs-2">Allowed Addresses</label>
						</div>
						<div class="col-md-8" id="cuscompany"> {{ $orderDetails->orderCustomer['allowed_addresses'] }}</div>
					</div>
				</div>
			</div>
		</div>
	</div>

<div class="cus_info">
	<div class="">
		<h3>Order Summary</h3>
		<div class="row">

			<div class="col-md-6">
				<div class="row">
					<div class="col-md-4">
						<label> Order id</label>
					</div>
					<div class="col-md-8" id="orderid">{{ $orderDetails['bigc_orderid']}}</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<label>Fright Amount</label>
					</div>
					<div class="col-md-8" id="base_handling_cost"><?php echo '$' .number_format($orderDetails['shipping_cost_tax'],2);?></div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<label>Product Subtotal</label>
					</div>
					<div class="col-md-8" id="base_handling_cost"><?php echo '$' .number_format($orderDetails['subtotal_inc_tax'],2);?></div>
				</div>
				
				
				<div class="row">
					<div class="col-md-4">
						<label>Subtotal Ex Tax </label>
					</div>
					
					<div class="col-md-8" id="base_handling_cost"><?php echo '$' .number_format($orderDetails['subtotal_ex_tax'],2);?></div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<label>Subtotal Tax</label>
					</div>
					<div class="col-md-8" id="base_handling_cost"><?php echo '$' .number_format($orderDetails['subtotal_tax'],2);?></div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<label>Subtotal INC Tax</label>
					</div>
					<div class="col-md-8" id="base_handling_cost"><?php echo '$' .number_format($orderDetails['subtotal_inc_tax'],2);?></div>
				</div>
	
			</div>
			<div class="col-md-6">
			<div class="row">
					<div class="col-md-4">
						<label>Payment Method</label>
					</div>

					<div class="col-md-8" id="coupon_discount">
						{{ $orderDetails['payment_method']}}
					</div>
				</div>
			<div class="row">
					<div class="col-md-4">
						<label>Custom Status </label>
					</div>

					<div class="col-md-8" id="custom_status">
						{{ $orderDetails['custom_status']}}
					</div>
				</div>
			<div class="row">
					<div class="col-md-4">
						<label>Cart Id</label>
					</div>
					<div class="col-md-8" id="cart_id">
						{{ $orderDetails['cart_id']}}
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<label>Coupon Discount</label>
					</div>

					<div class="col-md-8" id="coupon_discount">
						{{ $orderDetails['coupon_discount']}}
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<label>Country</label>
					</div>
					<div class="col-md-8" id="cart_id">
						{{ $orderDetails['geoip_country']}}
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-4">
						<label>Currency Code </label>
					</div>

					<div class="col-md-8" id="currency_code">
						{{ $orderDetails['currency_code']}}
					</div>
                    </div>
				</div>
			
			</div>
		</div>
	</div>
	<div class="w3-bar w3-black">
	<button class="w3-bar-item w3-button tablink w3-red" onclick="openOrder(event,'Customer')">Customer</button>
    <button class="w3-bar-item w3-button tablink" onclick="openOrder(event,'Billing')">Billing Address</button>
    <button class="w3-bar-item w3-button tablink" onclick="openOrder(event,'Shipping')">Shipping Address</button>
    <button class="w3-bar-item w3-button tablink" onclick="openOrder(event,'Item')">Items</button>
  </div>
  
      <div id="Customer" class="w3-container w3-border order">
		<div class="cus_info">
			<div class="">
				<div class="row">
					<div class="col-md-12">
						<h3>Customer Details</h3>
						<table class="table table-bordered data-table" id="table">
							<thead>
								<tr>
									<th>Customer No.</th>
									<th>Full Name</th>
									<th>Company Name</th>
									<th>Email</th>
									<th>Phone</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<tr>
								<td>{{ $orderDetails->orderCustomer['zoho_customer_no'] }}</td>
									<td>{{ $orderDetails->orderCustomer['first_name'] }} {{ $orderDetails->orderCustomer['last_name'] }}</td>
									<td>{{ $orderDetails->orderCustomer['company_name'] }}</td>
									<td>{{ $orderDetails->orderCustomer['email']}}</td>
									<td>{{ $orderDetails->orderCustomer['phone']}}</td>
										<input type="hidden" name="companyname" value="{{ $orderDetails->orderCustomer['company_name'] }}">
									<td><a class="btn btn-info"  href="/ordercustomer?id={{$orderDetails->orderBillingAddress['id']}}&orderid={{$orderDetails['id']}}&company={{ $orderDetails->orderCustomer['company_name']}}" role="button">Align</a></td>
								</tr>                                  
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
  
	
	  <div id="Item" class="w3-container w3-border order" style="display:none">
		<div class="cus_info">
			<div class="">
				<div class="row">
					<div class="col-md-12">
						<h3>Item Details</h3>
						<table class="table table-bordered data-table" id="table">
							<thead>
								<tr>
									<th>Sku</th>
									<th>Name</th>
									<th>order ID</th>
									<th>Product Id</th>
									<th>Variant Id</th>
									<th>Quantity</th>
									<th>Price</th>
									<th>Tax</th>
									<th>Total</th>
								</tr>
							</thead>
							<tbody>
								@foreach($orderDetails->orderItems as $itemList)
								<tr>
									<td id="sku">{{$itemList['sku'] }}</td>
									<td id="productname">{{$itemList['name'] }}</td>
									<td id="orderNo">{{ $itemList['order_id'] }}</td>
									<td id="product_id">{{$itemList['product_id'] }}</td>
									<td id="variant_id">{{$itemList['variant_id'] }}</td>
									<td id="quantity">{{$itemList['quantity'] }}</td>
									<td id="product_customer_name">{{$itemList['price_ex_tax'] }}</td>
									<td id="product_customer_name">{{$itemList['price_tax'] }}</td>
									<td id="product_customer_name">{{$itemList['total_inc_tax'] }}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	  <div id="Billing" class="w3-container w3-border order" style="display:none">
		<h3>Billing Address</h3>
		<table class="table table-bordered data-table" id="table">
			<thead>
				<tr>
				   <th>Customer No.</th>
				    <!-- <th>First Name</th>
					<th>Last Name</th> -->
					<!-- <th>Email</th> -->
					<th>Company Name</th>
					<th>Address 1</th>
					<th>Address 2</th>
					<th>City</th>
					<th>State</th>
					<!-- <th>Country</th>
					<th>Country Code</th> -->
					<th>Postal Code</th>
					<th>Phone</th>
					<th>Company Email</th>
					<th>Align</th>
				</tr>
			</thead>
			<tbody>
				<tr>
				<td>{{ $orderDetails->orderCustomer['zoho_customer_no'] }}</td>
					<td id="company">{{ $orderDetails->orderCustomer['company_name'] }}</td>
					<td id="street_1">{{ $orderDetails->orderBillingAddress['street_1']}}</td>
					<td id="street_2">{{ $orderDetails->orderBillingAddress['street_2']}}</td>
					<td id="city">{{ $orderDetails->orderBillingAddress['city']}}</td>
					<td id="state">{{ $orderDetails->orderBillingAddress['state']}}</td>
					<!-- <td id="country">{{ $orderDetails->orderBillingAddress['country']}}</td>
					<td id="country_iso2">{{ $orderDetails->orderBillingAddress['country_iso2']}}</td> -->
					<td id="zip">{{ $orderDetails->orderBillingAddress['zip']}}</td>
					<td id="phone">{{ $orderDetails->orderBillingAddress['phone']}}</td>
					<td id="zip">{{ $orderDetails->orderBillingAddress['email']}}</td>
					<input type="hidden" name="companyname" value="{{ $orderDetails->orderCustomer['company_name'] }}">
					<td><a class="btn btn-info"  href="/orderAddress?id={{$orderDetails->orderBillingAddress['id']}}&orderid={{$orderDetails['id']}}&company={{ $orderDetails->orderCustomer['company_name']}}" role="button">Align</a></td>
				</tr>                                  
			</tbody>
		</table>
	</div>

	 <div id="Shipping" class="w3-container w3-border order" style="display:none">
		<h3>Shipping Address</h3>
		<table class="table table-bordered data-table" id="table">
			<thead>
				<tr>
				  <th>Customer No.</th>
				    <!-- <th>First Name</th>
					<th>Last Name</th> -->
					<!-- <th>Email</th> -->
					<th>Company Name</th>
					<th>Address 1</th>
					<th>Address 2</th>
					<th>City</th>
					<th>State</th>
					<!-- <th>Country</th>
					<th>Country Code</th> -->
					<th>Postal Code</th>
					<th>Phone</th>
					<th>Company Email</th>
					<th>Align</th>
				</tr>
			</thead>
			<tbody>
				<tr>
				<td>{{ $orderDetails->orderCustomer['zoho_customer_no'] }}</td>

				<!-- <td id="first_name">{{ $orderDetails->orderShippingAddress['first_name']}}</td>
					<td id="last_name">{{ $orderDetails->orderShippingAddress['last_name']}}</td> -->
					<!-- <td id="email">{{ $orderDetails->orderShippingAddress['email']}}</td> -->
					<td id="company">{{ $orderDetails->orderBillingAddress['company']}}</td>
					<td id="street_1">{{ $orderDetails->orderShippingAddress['street_1']}}</td>
					<td id="street_2">{{ $orderDetails->orderShippingAddress['street_2']}}</td>
					<td id="city">{{ $orderDetails->orderShippingAddress['city']}}</td>
					<td id="state">{{ $orderDetails->orderShippingAddress['state']}}</td>
					<td id="zip">{{ $orderDetails->orderShippingAddress['zip']}}</td>
					<td id="phone">{{ $orderDetails->orderShippingAddress['phone']}}</td>
					<td id="email">{{ $orderDetails->orderShippingAddress['email']}}</td>	
					<!-- <td id="country">{{ $orderDetails->orderShippingAddress['country']}}</td>
					<td id="country_iso2">{{ $orderDetails->orderShippingAddress['country_iso2']}}</td> 
					 <td><a class="btn btn-info" href="{{URL::to('/orderSippingAddress')}}/{{ $orderDetails->orderShippingAddress['id'] }}" role="button">Align</a></td> -->
					<td><a class="btn btn-info"  href="/orderSippingAddress?id={{$orderDetails->orderShippingAddress['id']}}&orderid={{$orderDetails['id']}}&company={{ $orderDetails->orderCustomer['company_name']}}" role="button">Align</a></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="cus_info">		
		<div class="row">
				<!-- @if(!$isCustomerOnSpire)
				<div class="col-md-6">
					<label> Customer Number</label>
					<input type="text" name="customer_number" id="customer_number" value="" />
					<span class="error-info"></span>
				</div>
				@endif -->
				
			@if($orderDetails->orderCustomer['zoho_customer_no'] != "Unassigned" && ($orderDetails['payment_method']=="Credit Card" || $users['zoho_customer_no']!='Unassigned') )	
			<div class="">
				<h2>Send Order To SPIRE</h2>
				<button type="submit" value="save" id="confirmbox" class="btn btn-info" name="save" onClick="Alignaddresss()">Send Order</button>
	  		</div>
			@endif
		</div>
		</div>
	</div>

<script>
function Alignaddresss() {
    if(confirm("Are you sure you want to processed this order?")){
       Alignaddress()	   
    }
    else{  
		return false;
    }
}

function openOrder(evt, cityName) {
	var i, x, tablinks;
	x = document.getElementsByClassName("order");
	for (i = 0; i < x.length; i++) {
		x[i].style.display = "none";
	}
	tablinks = document.getElementsByClassName("tablink");
	for (i = 0; i < x.length; i++) {
		tablinks[i].className = tablinks[i].className.replace(" w3-red", "");
	}
	document.getElementById(cityName).style.display = "block";
	evt.currentTarget.className += " w3-red";
}

		function Alignaddress() {
			$('#overlay').css("display","block");
			var customer_number = $("#zohoCustomerNo").html();
			// $(".error-info").html('');
			// if ($("#customer_number").val() != 'undefined') {
			// 	customer_number =  $("#customer_number").val();
			// 	console.log("customer_number", customer_number);
			// 	if(!$.trim(customer_number).length) { // zero-length string AFTER a trim
			// 		$(".error-info").html('Please enter customer number');
			// 		return false;
			// 	} 
			// }


			var id = $("#orderid").html();
			var bigcommerce_order_id = $("#orderid").html();
			var base_handling_cost = $("#base_handling_cost").html();
			var base_shipping_cost = $("#base_shipping_cost").html();
			var base_wrapping_costmail = $("#base_wrapping_cost").html();
			var cart_id = $("#cart_id").html();
			var cuscompany = $("#cuscompany").html();
			var currency_code = $("#currency_code").html();
			var currency_exchange_rate = $("#currency_exchange_rate").html();
			var comname = $("#company").html();
			var custom_status = $("#custom_status").html();
			var country = $("#country").html();
			var customerNo = customer_number;
			var country_iso2 = $("#country_iso2").html();
			var city = $("#city").html();
			var email = $("#email").html();
			var provState = $("#state").html();
			var postalCode = $("#zip").html();
			var line1 = $("#street_1").html();
			var line2 = $("#street_2").html();
			var orderNo = $("#orderNo").html();
			var partNo = $("#sku").html();
			var orderQty = $("#quantity").html();
			var description = $("#productname").html();
			var shipcity = $("#shipcity").html();
			var shipcompany = $("#shipcompany").html();
			var shipcountry = $("#shipcountry").html();
			var shipcountry_iso2 = $("#shipcountry_iso2").html();
			var shipemail = $("#shipemail").html();
			var shipstate = $("#shipstate").html();
			var shipstreet_1 = $("#shipstreet_1").html();
			var shipstreet_2 = $("#shipstreet_2").html();
			var shipzip = $("#shipzip").html();
			var shipphone = $("#shipphone").html();
			var baseUrl = '{{config('config.Base_Url')}}';
			console.log(id);


			var data = {
						"_token": "{{ csrf_token() }}",
						"id": id,
						"country_iso2": country_iso2?country_iso2:'',
						"bigcommerce_order_id": bigcommerce_order_id?bigcommerce_order_id:'',
						"name": comname?comname:'',
						"country": country?country:'',
						"customerNo": customerNo?customerNo:'',
						"cuscompany": cuscompany?cuscompany:'',
						"description": description?description:'',
						"orderNo": orderNo?orderNo:'',
						"orderQty": orderQty?orderQty:'',
						"partNo": partNo?partNo:'',
						"email": email?email:'',
						"city": city?city:'',
						"provState": provState?provState:'',
						"postalCode": postalCode?postalCode:'',
						"line1": line1?line1:'',
						"line2": line2?line2:'',
						"shipcity": shipcity?shipcity:'',
						"shipcompany": shipcompany?shipcompany:'',
						"shipcountry": shipcountry?shipcountry:'',
						"shipcountry_iso2": shipcountry_iso2?shipcountry_iso2:'',
						"shipemail": shipemail?shipemail:'',
						"shipstreet_1": shipstreet_1?shipstreet_1:'',
						"shipstreet_2": shipstreet_2?shipstreet_2:'',
						"shipphone": shipphone?shipphone:''
					};
					console.log(data);


			$.ajax({
					type: 'post', // define the type of HTTP verb we want to use (POST for our form)
					url: baseUrl+'orderaligninspire', // the url where we want to POST
					data: {
						"_token": "{{ csrf_token() }}",
						"id": id,
						"country_iso2": country_iso2?country_iso2:'',
						"bigcommerce_order_id": bigcommerce_order_id?bigcommerce_order_id:'',
						"name": comname?comname:'',
						"country": country?country:'',
						"customerNo": customerNo?customerNo:'',
						"cuscompany": cuscompany?cuscompany:'',
						"description": description?description:'',
						"orderNo": orderNo?orderNo:'',
						"orderQty": orderQty?orderQty:'',
						"partNo": partNo?partNo:'',
						"email": email?email:'',
						"city": city?city:'',
						"provState": provState?provState:'',
						"postalCode": postalCode?postalCode:'',
						"line1": line1?line1:'',
						"line2": line2?line2:'',
						"shipcity": shipcity?shipcity:'',
						"shipcompany": shipcompany?shipcompany:'',
						"shipcountry": shipcountry?shipcountry:'',
						"shipcountry_iso2": shipcountry_iso2?shipcountry_iso2:'',
						"shipemail": shipemail?shipemail:'',
						"shipstreet_1": shipstreet_1?shipstreet_1:'',
						"shipstreet_2": shipstreet_2?shipstreet_2:'',
						"shipphone": shipphone?shipphone:''
					}, // our data object
					dataType: 'json', // what type of data do we expect back from the server
					encode: true
				})
				.done(function(data) {
					$('#overlay').css("display","none");
					console.log(data);
					if (data == 1) {
						alert("Order Sent Successfully");
						window.location = window.location.origin;
						//location.reload();
					} else {
						alert(data.comment);
						// location.reload();
					}
				}).fail(function(err) {					
					$('#overlay').css("display","none");
					alert("something went wrong!");
					console.log(err);
				});
		}
	</script>
</div>
</div>
@endsection