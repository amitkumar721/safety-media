@extends('layouts.app')
@section('content')

<style>
	.form-group {
		font-size: 18px;
	}
	.biling_address {
		padding: 10px;
	}
	.seachBlock{
		margin:30px 0;
	}
</style>
<div class="container-fluid" style="padding: 24px;">
	<div class="add">
		<a href="{{URL::to('/order')}}/{{ $orderid }}"><button type="button" class="btn btn-info">Back</button></a>
	</div>
	<table class="table table-bordered data-table" id="bigc_record">
		<thead>
			<tr>
				<th>
				@if(!$isCustomerOnSpire)
				Customer No.
				@else
				Spire Customer No.
				@endif
				</th>
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
				<input type = "hidden" id = "orderAddressId" value="{{ $orderAddressDetails['bigc_order_id'] }}">
				<input type = "hidden" id = "customer_id" value="{{ $orderAddressDetails->orderData->orderCustomer['bigc_customer_id'] }}">
				<input type = "hidden" id = "emailaddress" value="{{ $orderAddressDetails['email'] }}">
				
				@if($orderAddressDetails->orderData->orderCustomer['zoho_customer_no']=='Unassigned')
				<td id="customer_zoho_no" contenteditable></td>
			    @else
				<td id="customer_zoho_no" contenteditable>{{ $orderAddressDetails->orderData->orderCustomer['zoho_customer_no'] }}</td>
				@endif
				<td id="Company" contenteditable>{{$companyaddress}}</td>
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

<div class="seachBlock">
	<input type="text" id="companyref"  placeholder="Search Company Ref">  
	<input type="text" id="postalCode" placeholder="Search Postal Code">
	<input type="text" id="company" placeholder="Search Company">
	<input type="text" id="address" placeholder="Search Address">
	<input type="text" id="phone" min="1" onfocus="(this.type='number')" placeholder="Search Phone">
	<input type="text" id="email" placeholder="Search email">
	<button type="submit" class="btn btn-danger" onClick="searchAddressFromSpire()">Search</button>
</div>




<table class="table table-bordered data-table"  id ="tables" style="width:100%">
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
			<th>Select</th>
		</tr>
	</thead>
	<tbody id="example">

		@foreach ($customeraddressspire['records'] as $x => $details)
		<tr>
		
			<td id="aspireid">{{ $details['id'] }}</td>
			<td id="aspirelinkNo">{{ $details['linkNo'] }}</td>
			<td id="aspirename">{{ $details['name'] }}</td>
			<td id="aspireline1">{{ $details['line1'] }}</td>
			<td id="aspireline2">{{ $details['line2'] }}</td>
			<td id="aspirecity">{{ $details['city'] }}</td>
			<td id="aspireprovState">{{ $details['provState'] }}</td>
			<td id="aspirepostalCode">{{ $details['postalCode'] }}</td>
			<td id="aspirenumber">{{ $details['phone']['number'] }}</td>
			<td id="aspireemail">{{ $details['email'] }}</td>
			<input type = "hidden" id = "cutomeridnodata" value="{{ $details['linkNo'] }}">
			<td contenteditable><input type="radio" value="{{ $details['id'] }}" id="spireaddress{{$details['id']}}" name="spireaddress"></td>
		</tr>
		</tr>
		@endforeach
	</tbody>	
</table>

</div>
<!--
<select name="actiontype" id="addandupdaterecord" onChange="callAddUpdate(this.value)">
	 <option value="add_new">Add New</option>
	<option value="associaterecord">Associate Record</option>
</select>

<input type="button" id="addandupdatebutton" onClick="handleClick()" name="saveandupdate" value="Add new" />-->

<select name="actiontype" id="addandupdaterecord">
	<option value="associateRecordBigCommerce">Associate Record To BigCommerce</option>
	<option value="associaterecordSpire" id="spire_address">Associate Record To Spire</option>
	<option value="oneTimeOrder">One Time Order</option>
</select>

<input type="button" id="addandupdatebutton" onClick="handleClick()" name="saveandupdate" value="Associate" />

<!-- @if(empty($customernodata['records'][0]['customerNo']))
// <input type="button"  onClick="CreateSpireNumber()"  value="Create Customer Number" />
// @endif -->


<script src="https://code.jquery.com/jquery-3.5.1.js" crossorigin="anonymous"></script>
<script src="//cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js" defer></script>
<script>
	var customerNumber = "<?php echo $orderAddressDetails->orderData->orderCustomer['zoho_customer_no']; ?>";

	function searchAddressFromSpire() {
		var company = $("#company").val();
		var postalCode = $("#postalCode").val();
		var address = $("#address").val();
		var phone = $("#phone").val();
		var email = $("#email").val();
		var baseUrl = '{{config('config.Base_Url')}}';
		$.ajax({
				type: 'post', // define the type of HTTP verb we want to use (POST for our form)
				url: baseUrl+'search', // the url where we want to POST
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
						// $('#example').empty(row);
						// $('#example tbody').empty();
                            var arr=[];
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
								data.records[i].email + '</td><td contenteditable><input type="radio" class="radio" value="' + data.records[i].id + '" attribute= "' + data.records[i].linkNo+'" id="spireAddressId'+data.records[i].id+'" name="spireaddress" ></td></tr>');
							    arr.push(row);
						        $("#notfound").hide();
						}
                         $('#example').html(arr);
                        $('#tables').DataTable('refresh');
					}
				},
			).fail(function(err){
				console.log(err);
				alert("Somthing went worng!");
			});
	}

	// function callAddUpdate(str) {
	// 	if (str == "associaterecord") {
	// 		$('#addandupdatebutton').val('Associate');
	// 	} else {
	// 		$('#addandupdatebutton').val('Add new');
	// 	}
	// }

	$( document ).ready(function() {
		var bigCEmail = $("#Email").html();
		var spireBillingEmail = $("#aspireemail").html();
		if (bigCEmail == spireBillingEmail) {
			console.log('asd',spireBillingEmail);
			$('#bigc_record tbody tr').css('border-left', '5px solid green');
			$('#bigc_record tbody').append('<tr><td colspan="9" style="color:green;">This address is eligble to update on spire.<td></tr>');
		} else {
			$('#spire_address').hide();
		}
	});

	function handleClick() {		
		var actiontype = $("#addandupdaterecord").val();
		var id = $("#addressid").html();
		var name = $("#Company").html();
		var customerNoData = $("#cutomeridnodata").val();
		var phone = $("#Phone").html();
		var email = $("#Email").html();
		var line1 = $("#Address1").html();
		var line2 = ($("#Address2").html()).trim();
		var city = $("#City").html();
		var provState = $("#State").html();
		var postalCode = $("#Postal_code").html();
		var linkno = $("#customer_zoho_no").html();
		var customer_id = $("#customer_id").val();
		var orderAddressId = $("#orderAddressId").val();
		var baseUrl = '{{config('config.Base_Url')}}';
		var addressId = $('input[name="spireaddress"]:checked').val();
		var orderAddressId = $("#orderAddressId").val();
		
		var aspireid = $("#aspireid").html();
		var aspirelinkNo = $("#aspirelinkNo").html();
		var aspirename = $("#aspirename").html();
		var aspireline1 = $("#aspireline1").html();
		var aspireline2 = $("#aspireline2").html();
		var aspirecity = $("#aspirecity").html();
		var aspireprovState = $("#aspireprovState").html();
		var aspirepostalCode = $("#aspirepostalCode").html();
		var aspirenumber = $("#aspirenumber").html();
		var aspireemail = $("#aspireemail").html();
		
		if(email != aspireemail){
			alert("You are not authorised");
			location.reload();
			return false;
		}
		
		
		if (actiontype == "associaterecordSpire") {

			let payload = {
				"_token": "{{ csrf_token() }}",
				"id": id,
				"customer_id": customer_id,
				"name": name,
				"phone": phone,
				"email": email,
				"line1": line1,
				"line2": line2,
				"city": city,
				"provState": provState,
				"postalCode": postalCode,
				"linkno": linkno,
				"addressId": addressId,
				"orderAddressId": orderAddressId,
				"cutomerNo":customerNoData,
			};
			let method = "POST";
			let url = baseUrl + 'updatebillingAddressInSpire';

		let createBillingAddressOnSpireResponse = ajaxRequest(method, url, payload);
		console.log(createBillingAddressOnSpireResponse);
		return;


			$.ajax({
				type: 'POST', // define the type of HTTP verb we want to use (POST for our form)
				url: baseUrl+'updatebillingAddressInSpire', // the url where we want to POST
				data: {
					"_token": "{{ csrf_token() }}",
					"id": id,
					"customer_id": customer_id,
					"name": name,
					"phone": phone,
					"email": email,
					"line1": line1,
					"line2": line2,
					"city": city,
					"provState": provState,
					"postalCode": postalCode,
					"linkno": linkno,
					"addressId": addressId,
					"orderAddressId": orderAddressId,
					"cutomerNo":customerNoData,

				}, // our data object
				dataType: 'json', // what type of data do we expect back from the server
				encode: true
			}).done(function(data) {
				console.log("billingupdateSpire success", data);
			}).fail(function(err){
				console.log("billingupdateSpire erroe", err);
			});

			var addressid = $("#orderAddressId").html();
			var Company = $("#Company").html();
			var Phone = $("#Phone").html();
			var Email = $("#Email").html();
			var Address1 = $("#Address1").html();
			var line2 = $("#Address2").html();
			var City = $("#City").html();
			var State = $("#State").html();
			var Postal_code = $("#Postal_code").html();
			var customer_id = $("#customer_id").val();
			var customer_no = $("#customer_no").html();
			var linkno = $("#customer_zoho_no").html();
			var emailinfo=$("#email").val();
			$.ajax({
				type: 'put', // define the type of HTTP verb we want to use (POST for our form)
				url:  baseUrl+'address_update', // the url where we want to POST

				data: {
					"_token": "{{ csrf_token() }}",
					"id": addressid,
					"customer_id": customer_id,
					"company": Company,
					"phone": Phone,
					"email": Email,
					"street_1": Address1,
					"street_2": line2,
					"linkno": linkno,
					"city": City,
					"state": State,
					"zip": Postal_code,
					"addressId": addressId,
					"orderAddressId": orderAddressId,
					"customerformfieldzoho": customer_no,
					"email":emailinfo,
				}, // our data object
				dataType: 'json', // what type of data do we expect back from the server
				encode: true
			})
			// using the done promise callback
			.done(function(data) {
					if (data == 1) {
						alert("Address Update Successfully.");
						location.reload();
					}
				}
			).fail(function(err){
				console.log("billing address update error", err);
			});
		}

		//update data into spire and bigc if not found in spire than create new 
		// var radioValue = $("input[name='spireaddress']:checked").val();
		// if (radioValue)
		else if(actiontype = "associateRecordBigCommerce") {			
			var addressid = $("#orderAddressId").html();
			var Company = $("#Company").html();
			var Phone = $("#Phone").html();
			var Email = $("#Email").html();
			var Address1 = $("#Address1").html();
			var line2 = $("#Address2").html();
			var City = $("#City").html();
			var State = $("#State").html();
			var Postal_code = $("#Postal_code").html();
			var customer_id = $("#customer_id").val();
			var customer_no = $("#customer_no").html();
			var linkno = $("#customer_zoho_no").html();
			var emailinfo=$("#email").val();
			
			$.ajax({
				type: 'put', // define the type of HTTP verb we want to use (POST for our form)
				url:  baseUrl+'address_update', // the url where we want to POST

				data: {
					"_token": "{{ csrf_token() }}",
					"id": aspireid,
					"customer_id": customer_id,
					"company": aspirename,
					"phone": aspirenumber,
					"street_1": aspireline1,
					"street_2": aspireline2,
					"linkno": aspirelinkNo,
					"city": aspirecity,
					"state": aspireprovState,
					"zip": aspirepostalCode,
					"addressId": addressId,
					"orderAddressId": orderAddressId,
					"customerformfieldzoho": customer_no,
					"email":aspireemail,
				}, // our data object
				dataType: 'json', // what type of data do we expect back from the server
				encode: true
			})
			// using the done promise callback
			.done(function(data) {
					if (data == 1) {
						alert("Address Update Successfully.");
						location.reload();
					}
				}
			).fail(function(err){
				alert("Somthing went wrong!");
			});			
		} else {

		}
	}

	function CreateSpireNumber() {
		var customerno = $("#customer_zoho_no").html();
		var company= $("#Company").html();		
		
		if (customerno == '' || customerno == null || customerno == "" || customerno.length=="")  {
			alert("Please enter a valid customer number");
			return false;	
		}
		
		if (company=='')  {
			alert("Please enter a company name");
			return false;	 
		}
	
				
		var address= $("#Address1").html();
		var address2=$("#Address2").html();
		var city= $("#City").html();
		var state= $("#State").html();
		var postalcode= $("#Postal_code").html();
		var phone= $("#Phone").html();
		var customerid = $("#customer_id").val();
		var orderaddressid=$("#orderAddressId").val();
		var emailinfo= $("#email").html();
		var baseUrl = '{{config('config.Base_Url')}}';

		

		$.ajax({
			type: 'POST', // define the type of HTTP verb we want to use (POST for our form)
			url: baseUrl+'create_customer_no', // the url where we want to POST
			data: {
				"_token": "{{ csrf_token() }}",
				"customerNo": customerno,
				"companyinfo":company,
				"addressno":address,
				"addresssecond":address2,
				"cityinfo":city,
				"stateinfo":state,
				"postal":postalcode,
				"phoneinfo":phone,
				"customeridinfo":customerid,
				"oaddressid":orderaddressid,
				"email":emailinfo
			}, // our data object
			dataType: 'json', // what type of data do we expect back from the server
			encode: true,
			success: function(result) {
				if(result==201) {
					alert("This Customer create successfully.");
					location.reload();						
				} else if(result==400) {
					alert("This Customer Number already exist..");
						location.reload();						
				} else {
					alert("Something went wrong..");
					location.reload();						
				}					   
			},
			error: function(err){
				alert("somthing Worng!");
			}
		});
	}

	function ajaxRequest(method = null, url = null, payload = null){
		$.ajax({
			type: method,
			url:  url,
			data: JSON.stringify(payload), // our data object
			dataType: 'json', // what type of data do we expect back from the server
			encode: true
		}).done(function(data) {
			return true;		
		}).fail(function(err){
			return err;
		});
	}

	
</script>

</div>
@endsection