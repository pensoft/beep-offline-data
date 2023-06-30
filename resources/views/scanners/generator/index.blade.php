@extends('layout.app')

@section('content')
    <form action="{{ route('Scanner::Generator::create') }}" method="POST"
          enctype="multipart/form-data" id="form_generator">
        <div class="row" style="margin-bottom: 20px;">
            <label class="col-lg-12" for="language">
                Languages
            </label>
            <div class="col-lg-12">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="language[]" value="en">
                    <label class="form-check-label" for="same-address">English</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="language[]" value="bg">
                    <label class="form-check-label" for="same-address">Bulgarian</label>
                </div>
            </div>
        </div>

        <div class="row" style="margin-bottom: 20px;">
            <label class="col-lg-12" for="language">
                Blob Returns
            </label>
            <div class="col-lg-12">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="return_blob[]" value="text">
                    <label class="form-check-label" for="same-address">Text</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="return_blob[]" value="checkbox">
                    <label class="form-check-label" for="same-address">Checkbox</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="return_blob[]" value="number">
                    <label class="form-check-label" for="same-address">Number</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="return_blob[]" value="single-digit">
                    <label class="form-check-label" for="same-address">Single Digit</label>
                </div>
            </div>
        </div>

        <div class="row" style="margin-bottom: 20px;">
            <label class="col-lg-12" for="language">
                OCR Engine
            </label>
            <div class="col-lg-12">
                <select name="ocr_engine" id="ocr_engine" class="form-select">
                    <option value="tesseract">Tesseract</option>
                    <option value="aws">AWS Textract</option>
                </select>
            </div>
        </div>

        <div class="row" style="margin-bottom: 20px;">
            <label class="col-lg-12" for="svg">
                SVG
            </label>
            <div class="col-lg-12">
                <input type="file" class="form-control" name="svg" id="svg">
            </div>
        </div>

        <div class="row js-scan-wrapper" style="margin-bottom: 30px;">
            <label class="col-lg-12" for="scan">
                Scan
            </label>
            <div class="js-scan-input-wrapper">
                <div class="col-lg-12" style="margin-bottom: 20px;">
                    <input type="file" class="form-control" name="scan[]">
                </div>
            </div>
        </div>

        <div>
            @csrf

            <a href="javascript:;" class="btn btn-success js-add-scan" style="margin-right: 15px;">
                Add Scan
            </a>
            <button type="submit" name="generateRequest" class="btn btn-primary">Generate request</button>
        </div>
    </form>

@endsection


@section('page-plugins-scripts')
@endsection

@section('page-scripts')
    <script type="text/javascript">
        $(document).ready(function () {
            console.log('document is ready');

            $(document).on('click', '.js-add-scan', function() {
                console.log('click detected');
                $('.js-scan-wrapper').append($('.js-scan-input-wrapper').html());
            });
        });
    </script>
@endsection
