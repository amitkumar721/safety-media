@extends('layouts.app')
@section('content')
<div class="container-fluid" style="padding: 24px;">
    <table class="table table-bordered data-table"  id="table">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.23/css/jquery.dataTables.min.css"/>
        <thead>
   
            <tr>
            <th>Id</th>

                <th>Full Name</th>
                <th>Company</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>

            </tr>
        </thead>
        <tbody>
        @foreach ($data['data'] as $details)
        <tr>
        <td>{{ $details['id'] }}</td>
        <td>{{ $details['first_name'] }} {{ $details['last_name'] }}</td>
        <td>{{ $details['company'] }}</td>
        <td>{{ $details['email'] }}</td>
        <td>{{ $details['phone'] }}</td>
        <!-- <td><button type="button"  class="btn btn-primary" data-id="{{$details['id']}}" 
          data-toggle="modal" data-target="#exampleModal{{$details['id']}}" data-whatever="@mdo">View</button>
        <div class="modal fade" id="exampleModal{{$details['id']}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel-modal-lg">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="exampleModalLabel">User Details</h4>
      </div>
      <div class="modal-body">
      <div class="user_details">
      <div class="container">
      
      <div class="row">
      <div class="col-md-6">
          <div class="form-group">
            <label for="full-name" class="control-label">Full Name:</label>
            <input type="text" class="form-control" value="{{ $details['first_name'].' '.$details['last_name'] }}" id="recipient-name">
          </div>
          <div class="form-group">
            <label for="full-name" class="control-label">Company:</label>
            <input type="text" class="form-control" value="{{ $details['company']}}" >
          </div>
          <div class="form-group">
            <label for="full-name" class="control-label">Email:</label>
            <input type="text" class="form-control" value="{{ $details['email']}}">
          </div>
          <div class="form-group">
            <label for="full-name" class="control-label">Phone:</label>
            <input type="text" class="form-control" value="{{ $details['phone']}}">
          </div>
          </div>
          <div class="col-md-6">
          <div class="form-group">
            <label for="full-name" class="control-label">Date Created:</label>
            <input type="text" class="form-control" value="{{ $details['date_created'] }}">
          </div>
          <div class="form-group">
            <label for="full-name" class="control-label">Date Modified:</label>
            <input type="text" class="form-control" value="{{ $details['date_modified']}}">
          </div>
          <div class="form-group">
            <label for="full-name" class="control-label">Notes:</label>
            <input type="text" class="form-control" value="{{ $details['notes']}}">
          </div>
          </div>
          </div>
          </div> 
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Send message</button>
      </div>
    </div>
  </div>
</div>

</td> -->
        <td style="width: 20%;"><a href="{{URL::to('/customer_address')}}/{{ $details['id'] }}"><button type="button" class="btn btn-info" >Address</button></a>
        <a href="{{URL::to('/order')}}/{{ $details['id'] }}"><button type="button" class="btn btn-info" >Order</button></a>
        </td>
        </tr>
        @endforeach
        </tbody>
    </table>

   
<script
  src="https://code.jquery.com/jquery-3.5.1.js"
  integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc="
  crossorigin="anonymous"></script>
<!-- <script src="{{ asset('js/app.js') }}" defer></script> -->
<script src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js
" defer></script>
    <script>
  $(document).ready(function() {
    $('#table').DataTable();
} );
 </script>
</div>
@endsection