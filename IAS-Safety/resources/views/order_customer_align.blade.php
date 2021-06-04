@extends('layouts.app')
@section('content')

<style>
	.form-group {
		font-size: 18px;
	}
	.biling_address {
		padding: 10px;
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
	<div class="add">
		<a href="{{URL::to('/order')}}/{{ $orderid }}"><button type="button" class="btn btn-info">Back</button></a>
	</div>
	<table class="table table-bordered data-table">
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


	<!-- <input type="text" id="companyref"  placeholder="Search Company Ref">   -->
	@if($orderAddressDetails->orderData->orderCustomer['zoho_customer_no']=='Unassigned')
	<input type="text" id="postalCode" placeholder="Search Postal Code">
	<input type="text" id="company" placeholder="Search Company">
	<input type="text" id="address" placeholder="Search Address">
	<input type="text" id="phone" min="1" onfocus="(this.type='number')" placeholder="Search Phone">
	<input type="text" id="email" placeholder="Search email">
	<button type="submit" class="btn btn-danger" onClick="address()">
		Search
	</button><br>
	@endif





<table class="table table-bordered data-table"  id ="table" style="width:100%">
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
		
			<td>{{ $details['id'] }}</td>
			<td>{{ $details['linkNo'] }}</td>
			<td>{{ $details['name'] }}</td>
			<td>{{ $details['line1'] }}</td>
			<td>{{ $details['line2'] }}</td>
			<td>{{ $details['city'] }}</td>
			<td>{{ $details['provState'] }}</td>
			<td>{{ $details['postalCode'] }}</td>
			<td>{{ $details['phone']['number'] }}</td>
			<td>{{ $details['email'] }}</td>
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
@if($orderAddressDetails->orderData->orderCustomer['zoho_customer_no']=='Unassigned')
<select name="actiontype" id="addandupdaterecord">
	<option value="associaterecord">Associate Record</option>
</select>

<input type="button" id="addandupdatebutton" onClick="handleClick()" name="saveandupdate" value="Associate" />
@endif

@if(empty($customernodata['records'][0]['customerNo']))
<input type="button"  onClick="CreateSpireNumber()"  value="Create Customer Number" />
@endif


<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="//cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js" defer></script>
<script>
$(document).ready( function () {
   
} );
	var customerNumber = "<?php echo $orderAddressDetails->orderData->orderCustomer['zoho_customer_no']; ?>";

	function address() {
		$('#overlay').css("display","block");
		var company = $("#company").val();
		var postalCode = $("#postalCode").val();
		var address = $("#address").val();
		var phone = $("#phone").val();
		var email = $("#email").val();
		var baseUrl = '{{config('config.Base_Url')}}';
		var table = $('#table').DataTable();
		 table.destroy();
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
				$('#overlay').css("display","none");
					console.log(data);					
					//alert(data.records.length);
					if (data == 0) {
						$('#example').empty(row);
						$("#notfound").show();
					} else if (data.records.length == 0) {
						$('#example').empty(row);
						$("#notfound").show();
					} else {						
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
                        $('#table').DataTable('refresh');
                            // empty in case the columns change
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
		var actiontype = $("#addandupdaterecord").val();
		var id = $("#addressid").html();
		var name = $("#Company").html();
		var customerNoData = $("#cutomeridnodata").val();
		var phone = $("#Phone").html();
		var email = $("#Email").html();
		var line1 = $("#Address1").html();
		var line2 = $("#Address2").html();
		var city = $("#City").html();
		var provState = $("#State").html();
		var postalCode = $("#Postal_code").html();
		var linkno = $("#customer_zoho_no").html();
		var customer_id = $("#customer_id").val();
		var orderAddressId = $("#orderAddressId").val();
		var baseUrl = '{{config('config.Base_Url')}}';
		var addressId = $('input[name="spireaddress"]:checked').val();
		

		if (actiontype == "add_new") {
					
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
							"orderAddressId": orderAddressId

						}, // our data object
						dataType: 'json', // what type of data do we expect back from the server
						encode: true
					})



	// using the done promise callback
	.done(function(data) {
			if (data == 1) {
				//alert("Address Insert Successfully.");
				// location.reload();


			}



		}

	);
}
else {
			//update data into spire and bigc if not found in spire than create new 
			// var radioValue = $("input[name='spireaddress']:checked").val();
			// if (radioValue) {
				
				var radios =  $("input[name=spireaddress]").is(":checked");
				
				if(radios==false)
				{
					alert("please choose any option");
					return false;
				}
				
				
				
				
				var customerno = $("#customer_zoho_no").html();
		
		
	
	if (customerno == '' || customerno == null || customerno == "" || customerno.length=="")  
			
			{
					
					var searchid=$('input[name="spireaddress"]:checked').val();
					var sname = $("#spireAddressId"+searchid).attr("attribute");
					var companyname =$("#name-"+searchid).html();
					var address1=  $("#line1-"+searchid).html();
					var address2= $("#line2-"+searchid).html();
					var city=$("#city-"+searchid).html();
					var provstate= $("#provState-"+searchid).html();
					var postalCode= $("#postalCode-"+searchid).html();    
					var phone=	$("#phone-"+searchid).html();
					var email=	$("#email-"+searchid).html();
					var customerid=	$("#customer_id").val();
					var addressidinfo =  $("#orderAddressId").val();
					
					
					$.ajax({
						type: 'POST', // define the type of HTTP verb we want to use (POST for our form)
						url: baseUrl+'updatewithoutcustomernumber', // the url where we want to POST
						data: {
							"_token": "{{ csrf_token() }}",
							"addressid": searchid,
							"spirecustomername": sname,
							"companyname":companyname,
							"address1":address1,
							"address2":address2,
							"city":city,
							"provstate":provstate,
							"postalCode":postalCode,
							"phone":phone,
							"email":email,
							"customerid":customerid,
							"addressinfoid":addressidinfo,
							
							

						}, 
						
						// our data object
						dataType: 'json', // what type of data do we expect back from the server
						encode: true,
						 success: function(result){
                        alert("Customer create successfully.");
                          location.reload();						
                       
                        
                           // location.reload();											   
                           },
						   error: function(error){
							   console.log(error);
							alert("Somethig Went wrong.");
						  }
						 
					});

						
					}
				
				// else{
				
				
				// var orderAddressId = $("#orderAddressId").val();
				// console.log('Test1'+orderAddressId);

				// $.ajax({
						// type: 'POST', // define the type of HTTP verb we want to use (POST for our form)
						// url: baseUrl+'updatebillingAddressInSpire', // the url where we want to POST
						// data: {
							// "_token": "{{ csrf_token() }}",
							// "id": id,
							// "customer_id": customer_id,
							// "name": name,
							// "phone": phone,
							// "email": email,
							// "line1": line1,
							// "line2": line2,
							// "city": city,
							// "provState": provState,
							// "postalCode": postalCode,
							// "linkno": linkno,
							// "addressId": addressId,
							// "orderAddressId": orderAddressId,
							// "cutomerNo":customerNoData,

						// }, // our data object
						// dataType: 'json', // what type of data do we expect back from the server
						// encode: true
					// })


					//using the done promise callback
					// .done(function(data) {
							// if (data == 1) {
								//location.reload();

							// }



						// }

					// );


				// var addressid = $("#orderAddressId").html();
				// var Company = $("#Company").html();
				// var Phone = $("#Phone").html();
				// var Email = $("#Email").html();
				// var Address1 = $("#Address1").html();
				// var line2 = $("#Address2").html();
				// var City = $("#City").html();
				// var State = $("#State").html();
				// var Postal_code = $("#Postal_code").html();
				// var customer_id = $("#customer_id").val();
				// var customer_no = $("#customer_no").html();
				// var linkno = $("#customer_zoho_no").html();
                 // var emailinfo=$("#email").val();


				// $.ajax({
						// type: 'put', // define the type of HTTP verb we want to use (POST for our form)
						// url:  baseUrl+'address_update', // the url where we want to POST

						// data: {
							// "_token": "{{ csrf_token() }}",
							// "id": addressid,
							// "customer_id": customer_id,
							// "company": Company,
							// "phone": Phone,
							// "email": Email,
							// "street_1": Address1,
							// "street_2": line2,
							// "linkno": linkno,
							// "city": City,
							// "state": State,
							// "zip": Postal_code,
							// "addressId": addressId,
							// "orderAddressId": orderAddressId,
							// "customerformfieldzoho": customer_no,
							// "email":emailinfo,
						// }, // our data object
						// dataType: 'json', // what type of data do we expect back from the server
						// encode: true
					// })

					//using the done promise callback
					// .done(function(data) {
							// if (data == 1) {
								// alert("Address Update Successfully.");
								// location.reload();

							// }



						// }

					// );
				// }
	}
}
function CreateSpireNumber() {
	$('#overlay').css("display","block");
	var customerno = $("#customer_zoho_no").html();
	var company= $("#Company").html();
		
	
	if (customerno == '' || customerno == null || customerno == "" || customerno.length=="")  {
		$('#overlay').css("display","none");
		alert("Please enter a valid customer number");
		return false;	
	}
	
	if (company == '')  {
		$('#overlay').css("display","none");
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
	var emailinfo= $("#emailaddress").val();
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
							"email":emailinfo,							
						}, // our data object
						dataType: 'json', // what type of data do we expect back from the server
						encode: true,
						success: function(result) {
							$('#overlay').css("display","none");
							console.log(result);
							alert(result); 
							location.reload();
																			   
                        },
						error: function(XMLHttpRequest, textStatus, errorThrown) { 
							$('#overlay').css("display","none");
							console.log(errorThrown);
							alert('Somthing went wrong!');
						}
					});
                   

	}

	
</script>
<script  src="https://code.jquery.com/jquery-3.5.1.js"></script>
<!-- <script src="{{ asset('js/app.js') }}" defer></script> -->
<script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js" defer></script>
<script>
  $(document).ready(function() {
    $('#table').DataTable({
		'paging': true
	});
} );
</script>
</div>
@endsection