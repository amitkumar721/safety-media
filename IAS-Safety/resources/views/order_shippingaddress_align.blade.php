@extends('layouts.app')
@section('content')

<style>
	.form-group {
		font-size: 18px;
	}

	.biling_address {
		padding: 10px;
	}
</style>
<div class="container-fluid" style="padding: 24px;">
	<div class="add">
		<a href="{{URL::to('/order')}}/{{ $orderid }}"><button type="button" class="btn btn-info">Back</button></a>
	</div>
	<table class="table table-bordered data-table" id="table">
		<thead>
			<tr>
				<th>
				@if(!$isCustomerOnSpire)
				Customer No.
				@else
				Spire Customer No.
				@endif</th>
				<th>Company Name</th>
				<th>Address 1</th>
				<th>Address 2</th>
				<th>City</th>
				<th>State</th>
				<th>Postal Code</th>
				<th>Telephone</th>
				<th>Company Email</th>
			</tr>
		</thead>
		<tbody>
			@if($orderAddressDetails)

			<tr>
			    <!-- <td id="addressid" contenteditable>{{ $orderAddressDetails['bigc_order_id'] }}</td> -->
				<input type = "hidden" id = "orderAddressId" value="{{ $orderAddressDetails['bigc_order_id'] }}">
				<input type = "hidden" id="customer_id" value="{{ $orderAddressDetails->orderData->orderCustomer['bigc_customer_id'] }}">
				<td id="Company" contenteditable>{{ $orderAddressDetails->orderData->orderCustomer['zoho_customer_no'] }}</td>
				<td id="Companyname" contenteditable>{{ $companyaddress }} </td>
				<td id="Address1" contenteditable>{{ $orderAddressDetails['street_1'] }}</td>
				<td id="Address2" contenteditable>{{ $orderAddressDetails['street_2'] }}</td>
				<td id="City" contenteditable>{{ $orderAddressDetails['city'] }}</td>
				<td id="State" contenteditable>{{ $orderAddressDetails['state'] }}</td>
				<td id="Postal_code" contenteditable>{{ $orderAddressDetails['zip'] }}</td>
				<td id="Phone" contenteditable>{{ $orderAddressDetails['phone'] }}</td>
				<td id="Email" contenteditable>{{ $orderAddressDetails->orderData->orderCustomer['email'] }}</td>
				</td>
			</tr>
			@endif

		</tbody>
	</table>
	<!--<input type="text" id="postalCode" placeholder="Search Postal Code">
	<input type="text" id="company" placeholder="Search Company">
	<input type="text" id="address" placeholder="Search Address">
	<input type="text" id="phone" min="1" onfocus="(this.type='number')" placeholder="Search Phone">
	<input type="text" id="email" placeholder="Search Email">

	<button type="submit" class="btn btn-danger" onClick="address()">
		Search
	</button><br>-->





<table class="table table-bordered data-table" style="width:100%">
	<thead>
		<tr>
			<th>ID</th>
			<th>Spire Customer No.</th>
			<th>Company Name</th>
			<th>Address1</th>
			<th>Address2</th>
			<th>City</th>
			<th>State</th>
			<th>Postal Code</th>
			<th>Telephone</th>
			<th>Company Email</th>
			<!--<th>Select</th>-->
		</tr>
	</thead>
	<tbody id="example">
		@foreach ($customeraddressspire['records'] as $x => $details)
		<tr>
			<td id = "spireaddressid">{{ $details['id'] }}</td>
			<td>{{ $details['linkNo'] }}</td>
			<td>{{ $details['name'] }}</td>
			<td>{{ $details['line1'] }}</td>
			<td>{{ $details['line2'] }}</td>
			<td>{{ $details['city'] }}</td>
			<td>{{ $details['provState'] }}</td>
			<td>{{ $details['postalCode'] }}</td>
			<td>{{ $details['phone']['number'] }}</td>
			<td>{{ $details['email'] }}</td>
			<!--<td contenteditable><input type="radio" value="{{ $details['id'] }}" class="radiochoose" id="spireaddress{{$details['id']}}" name="spireaddress"></td>-->
		</tr>
		</tr>
		@endforeach

	</tbody>

</table>
</div>
@if($orderAddressDetails->orderData->orderCustomer['zoho_customer_no'] != "Unassigned")
	<!--
<select name="actiontype" id="addandupdaterecord" onChange="callAddUpdate(this.value)">
	<option value="add_new">Add New</option>
	<option value="associaterecord">Associate Record</option>
</select>
-->
<button type="button" id="addandupdatebutton" onClick="handleClick()" name="saveandupdate"> Add new</button>
@endif

<script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
<script src="//cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js" defer></script>
<script>
	var customerNumber = "<?php echo $orderAddressDetails->orderData->orderCustomer['zoho_customer_no']; ?>";

	function address() {
		//--- some logic

		var company = $("#company").val();
		var postalCode = $("#postalCode").val();
		var address = $("#address").val();
		var phone = $("#phone").val();
		var email = $("#email").val();

		var baseUrl = '{{config('config.Base_Url')}}';
		//  alert(add);
		$.ajax({
				type: 'post', // define the type of HTTP verb we want to use (POST for our form)
				url: baseUrl+'shippingsearch', // the url where we want to POST
				data: {
					"_token": "{{ csrf_token() }}",
					"customerNumber": customerNumber,
					"company": company,
					"postalCode": postalCode,
					"line1": address,
					"phone": phone,
					"email": email
				}, // our data object
				dataType: 'json', // what type of data do we expect back from the server
				encode: true
			})

			// using the done promise callback
			.done(function(data) {
					console.log(data);
					//alert(data.records.length);
					if (data == 0) {
						$('#example').empty(row);
						$("#notfound").show();
					} else if (data.records.length == 0) {
						$('#example').empty(row);
						$("#notfound").show();
					} else {
						$('#example').empty(row);

						for (var i = 0; i < data.records.length; i++) {

							var row = $('<tr><td contenteditable id="id-' + data.records[i].id + '">' +
								data.records[i].id + '</td><td contenteditable id="linkno-' + data.records[i].id + '">' +
								data.records[i].linkNo + '</td><td contenteditable id="name-' + data.records[i].id + '">' +
								data.records[i].name + '</td><td id="line1-' + data.records[i].id + '">' +
								data.records[i].line1 + '</td><td contenteditable id="line2-' + data.records[i].id + '">' +
								data.records[i].line2 + '</td><td contenteditable id="city-' + data.records[i].id + '">' +
								data.records[i].city + '</td><td contenteditable id="provState-' + data.records[i].id + '">' +
								data.records[i].provState + '</td><td contenteditable id="postalCode-' + data.records[i].id + '">' +
								data.records[i].postalCode + '</td><td contenteditable id="phone-' + data.records[i].id + '">' +
								data.records[i].phone.number + '</td><td contenteditable id="email-' + data.records[i].id + '">' +
								data.records[i].email + '</td><td contenteditable><input type="radio" value="' + data.records[i].id + '"  id="spireAddressId" name="spireaddress" ></td></tr>');

							$('#example').append(row);
							// console.log(row);
							$("#notfound").hide();
						}




					}


				},

			);


	}

	function callAddUpdate(str) {
		if (str == "associaterecord") {
			$('#addandupdatebutton').val('Associate');
		} else {
			$('#addandupdatebutton').val('Add new');
		}
	}
	function handleClick() {
		
		//getting data from view
		var id = $("#addressid").html();
		var name = $("#Company").html();
		var companyname = $("#Companyname").html();
		var phone = $("#Phone").html();
		var email = $("#Email").html();
		var line1 = $("#Address1").html();
		var line2 = $("#Address2").html();
		var city = $("#City").html();
		var spireaddressid = $("#spireaddressid").html();
		var provState = $("#State").html();
		var postalCode = $("#Postal_code").html();
		var linkno = $("#customer_zoho_no").val();
		var customer_id = $("#customer_id").val();
		var orderAddressId = $("#orderAddressId").val();
		var baseUrl = '{{config('config.Base_Url')}}';
		var addressId = $('input[name="spireaddress"]:checked').val();
		//console.log(orderAddressId);



		
			$.ajax({
					type: 'post', // define the type of HTTP verb we want to use (POST for our form)
					url: baseUrl+'address_create_spire', // the url where we want to POST
					data: {
						"_token": "{{ csrf_token() }}",
						"id": id,
						"customer_id": customer_id,
						"companyinfo":companyname,
						"name": name,
						"phone": phone,
						"email": email,
						"line1": line1,
						"line2": line2,
						"city": city,
						"provState": provState,
						"postalCode": postalCode,
						"linkno": linkno
					}, // our data object
					dataType: 'json', // what type of data do we expect back from the server
					encode: true
				})


				// using the done promise callback
				.done(function(data) {
					
						if (data == 400) {
							alert("error:same address not created");
							location.reload();


						}

                     else if(data==201)
					 {
						alert("address create successfully");
							location.reload(); 
					 }

					}

				);
		
	
		}
		
	
</script>
</div>
@endsection