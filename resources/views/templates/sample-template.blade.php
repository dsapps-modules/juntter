@extends('templates.base-template')


@push('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <style>
        a.nostyle:link, a.nostyle:visited {
            text-decoration: inherit;
            color: inherit;
            cursor: auto;
        }
    </style>

@endpush



@section('header')
    <body class="hold-transition layout-top-nav">
        <div class="wrapper">
            
		</div>

@endsection



@section('content')
			<div class="content-wrapper">
				<div class="content-header">
					@yield('breadcrumb')
				</div>

                {{ $data['nome'] ?? 'Default Name'}}

				<div class="{{$page_width ?? 'container'}}">
					@yield('page')
				</div>
			</div>

		</div>

@endsection



@push('scripts')
    <script>
        $(document).ready(function(){
            setTimeout(function() {
                $('#success-display').hide(500)
            }, 4000);
        });
    </script>
@endpush