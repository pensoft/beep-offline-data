@extends('layout.app')

@section('content')
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-lg-12">
            Request Headers
        </div>
        <div class="col-lg-12">
            <pre>{{ json_encode(($headers ?? []), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-lg-12">
            Request Body
        </div>
        <div class="col-lg-12">
            <pre>{{ json_encode(($body ?? []), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
@endsection


@section('page-plugins-scripts')
@endsection

@section('page-scripts')
@endsection
