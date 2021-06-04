<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{URL::to('/')}}" class="brand-link">
        <!-- <img src="https://cdn11.bigcommerce.com/s-z84xkjcnbz/images/stencil/250x100/image001_1612874787__41966.original.png"
             alt="Safety Media"
             class="brand-image img-circle elevation-3"> -->
        <span class="brand-text font-weight-light" style="visibility: hidden;">{{ config('app.name') }}</span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                @include('layouts.menu')
            </ul>
        </nav>
    </div>

</aside>
