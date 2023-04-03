@extends('layout.app')

@section('content')
    <div class="row mb-5">
        <div class="col-lg-12">
            <pre>{!! json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) !!}</pre>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <a href="{{ $imageUrl }}" target="_blank" style="cursor: zoom-in;">
                <img src="{{ $imageUrl }}" alt="Image" class="w-100">
            </a>
        </div>
        <div class="col-lg-4 text-start">
            <pre>{{ json_encode(json_decode(trim($json)), JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
@endsection


@section('page-plugins-scripts')
@endsection

@section('page-scripts')
@endsection
