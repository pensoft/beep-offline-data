@extends('layout.app')

@section('content')
    <div class="row">
        <div class="col-lg-4">
            1
        </div>

        <div class="col-lg-4">
            2
        </div>

        <div class="col-lg-4">
            3
        </div>
    </div>

@endsection


@section('page-plugins-scripts')
@endsection

@section('page-scripts')
    <script type="text/javascript">
        console.log('Test');
        $(document).ready(function() {
            console.log('Test ready');
        });
    </script>
@endsection
