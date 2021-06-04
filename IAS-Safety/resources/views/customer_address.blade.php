@extends('layouts.app')
@section('content')

<div class="container-fluid" style="padding: 24px;">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<div class="add">
    <a href="{{URL::to('/')}}"><button type="button" class="btn btn-info">Back</button></a>
    </div>
<h2>Customer Address</h2>


<div class="w3-container">


  <div class="w3-bar w3-black">
    <button class="w3-bar-item w3-button tablink w3-red" onclick="openCity('Billing')">Billing</button>
    <button class="w3-bar-item w3-button tablink" onclick="openCity('Shipping')">Shipping</button>
  </div>
  @foreach ($shippingdata['data'] as $shipping)
  @if($shipping['address_type'] =='residential')
  <div id="Billing" class="w3-container w3-border city">
    <h2>Billing Address</h2>
  <table class="table table-bordered data-table"  id="table">
        <thead>
   
            <tr>
            <th>ID</th>
                <th>Full Name</th>
                <th>Company</th>
                <th>Address1</th>
                <th>Address 2</th>
                <th>City</th>
                <th>State</th>
                <th>Country</th>
                <th>Country Code</th>
                <th>Postal Code</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <tr>
        <td>{{ $shipping['id'] }}</td>
        <td>{{ $shipping['first_name'] }} {{ $shipping['last_name'] }}</td>
        <td>{{ $shipping['company'] }}</td>
        <td>{{ $shipping['address1'] }}</td>
        <td>{{ $shipping['address2'] }}</td>
        <td >{{ $shipping['city'] }}</td>
        <td>{{ $shipping['state_or_province'] }}</td>
        <td>{{ $shipping['country'] }}</td>
        <td>{{ $shipping['country_code'] }}</td>
        <td>{{ $shipping['postal_code'] }}</td>
        <td>{{ $shipping['phone'] }}</td>

        <td><a href="{{URL::to('/customerbilling')}}/{{ $shipping['id'] }}"><button type="button" class="btn btn-info" >Align</button></a></td>
        </tr>
        
        </tbody>
    </table>
  </div>
  @endif
@if($shipping['address_type'] =='commercial')
  <div id="Shipping" class="w3-container w3-border city" style="display:none">
    <h2>Shipping Address</h2>
    <table class="table table-bordered data-table"  id="table">
        <thead>
   
            <tr>
              <th>ID</th>
                <th>Full Name</th>
                <th>Company</th>
                <th>Address1</th>
                <th>Address2</th>
                <th>City</th>
                <th>State</th>
                <th>Country</th>
                <th>Country Code</th>
                <th>Postal Code</th>
                <th>Phone</th>
                <th>Action</th>
                
            </tr>
        </thead>
        <tbody>
        <tr>
        <td >{{ $shipping['id'] }}</td>
        <td >{{ $shipping['first_name'] }} {{ $shipping['last_name'] }}</td>
        <td>{{ $shipping['company'] }}</td>
        <td>{{ $shipping['address1'] }}</td>
        <td>{{ $shipping['address2'] }}</td>
        <td>{{ $shipping['city'] }}</td>
        <td>{{ $shipping['state_or_province'] }}</td>
        <td>{{ $shipping['country'] }}</td>
        <td>{{ $shipping['country_code'] }}</td>
        <td>{{ $shipping['postal_code'] }}</td>
        <td>{{ $shipping['phone'] }}</td>
        <td><a href="{{URL::to('/customershipping')}}/{{ $shipping['id'] }}"><button type="button" class="btn btn-info" >Align</button></a></td>
        </tr>
                </tbody>
    </table>
      </div>
      @endif
@endforeach
</div>

<script>
function openCity(cityName) {
  var i;
  var x = document.getElementsByClassName("city");
  for (i = 0; i < x.length; i++) {
    x[i].style.display = "none";  
  }
  document.getElementById(cityName).style.display = "block";  
}
</script>
</div>
@endsection