# Beep - Pensoft

Endpoint for Optical Character Recognition (OCR) and Optical mark Recognition (OMR)

## Requirements

* [PHP](https://www.php.net/releases/8.1/en.php) v8.1
* [Composer](https://getcomposer.org/download) v2.5 and higher.
* [PHP Imagick](https://www.php.net/manual/en/book.imagick.php) extension ext-imagick
* [PHP DOMDocument](https://www.php.net/manual/en/class.domdocument.php) extension ext-dom
* [Tesseract OCR](https://tesseract-ocr.github.io/) extension and language packs tesseract-ocr, tesseract-ocr-eng and tesseract-ocr-bul

## Installation

Install the required extensions
```bash
sudo apt update

#Imagick
sudo apt install imagemagick
sudo apt install php-imagick

#DOMDocument
sudo apt install php-xml

#TesseractOCR
sudo apt install tesseract-ocr
sudo apt install tesseract-ocr-eng
sudo apt install tesseract-ocr-bul
```

Clone the repository [pensoft-beep-scanner](https://bitbucket.org/scalewest/pensoft-beep-scanner) in the public_html folder.
```bash
git pull
```

Install [Laravel](https://laravel.com/) dependencies in the public_html folder
```bash
composer install
```

## Configuration

Project configuration
```bash
# create & configure the ENV file
cp .env.example .env
php artisan key:generate

Update the APP_NAME, APP_ENV, APP_DEBUG, APP_URL and LOG_LEVEL
Set SCANNER_TOKEN_SECRET for the API token
```

Environment settings in .env file
```bash
# AWS Textract required settings
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_TEXTRACT_VERSION='latest'

# Project required settings
SCANNER_TOKEN_SECRET="token_hash"
SCANNER_BASE_DIRECTORY="app/public/scanner/scans"
SCANNER_MODE_DEBUG=true
SCANNER_LOG_MODE=debug
SCANNER_AREA_ADJUSTMENT_PERCENTAGE=90
SCANNER_CHECKBOX_TOLERANCE=25
SVG_PAGE_WIDTH_MM=210
SVG_PAGE_HEIGHT_MM=297
SCANNER_DEFAULT_OCR_ENGINE="tesseract"
```

#### AWS Textract access key
* In AWS go IAM / users and add a new user
* Set a user name
* Add the following permissions: "AmazonTextractFullAccess"
* Once the user is created, go to the tab "Security credentials" and create the access keys
* Set the "Access key" in the .env file for "AWS_ACCESS_KEY_ID" and the "Secret access key" for "AWS_SECRET_ACCESS_KEY" and the region for AWS_DEFAULT_REGION 

#### Minimum recommended PHP settings
```bash
# AVG running time for 1 page is 1 min. We can set 20 min for 15 pages
max_execution_time 1200
max_input_time	1200
memory_limit 2048M
post_max_size 512M
upload_max_filesize 128M
```

#### Minimum recommended PHP Imagick settings
```bash
# File location
/etc/ImageMagick-6/policy.xml

# settings
<policy domain="resource" name="memory" value="1024MiB"/>
<policy domain="resource" name="map" value="2048MiB"/>
<policy domain="resource" name="width" value="16KP"/>
<policy domain="resource" name="height" value="16KP"/>
<policy domain="resource" name="area" value="128MB"/>
<policy domain="resource" name="disk" value="4GiB"/>
```

#### Install TesseractOCR trained data from 2017
```bash
# Source location
/{PATH-TO-PROJECT}/storage/app/tesseract/

# Destination location
/usr/share/tesseract-ocr/4.00/tesseract/tessdata/

# You can backup the original trained data before replacement

# Copy the trained data
sudo cp /{PATH-TO-PROJECT}/storage/app/tesseract/eng.traineddata /usr/share/tesseract-ocr/4.00/tesseract/tessdata/eng.traineddata
sudo cp /{PATH-TO-PROJECT}/storage/app/tesseract/bul.traineddata /usr/share/tesseract-ocr/4.00/tesseract/tessdata/bul.traineddata
```

## Deployment

Required deployment commands
```bash
git pull
composer install
```

## Usage

OCR & OMR Scanner

```bash
# Endpoint URL
https://{DOMAIN-NAME}/api/scanner/scan

# HTTP Request Method
POST

# Headers
{
    "token": "SCANNER_TOKEN_SECRET from .env file"
}

# Body
{
    "svg": "SVG SCHEMA CONTENTS",
    "images": [
        {
            "page": 1,
            "image": "data:image/JPG;base64,SCANNED_DOCUMENT_BASED_64_ENCODED"
        },
        {
            "page": 1,
            "image": "data:image/JPG;base64,SCANNED_DOCUMENT_BASED_64_ENCODED"
        }
    ],
    "settings": {
        "return_blob": ["text", "number", "single-digit", "checkbox"]
    },
    "data-user-locale": ["en"]
}

# Request generator can be accessed at this address
https://{DOMAIN-NAME}/scanner/generator
```
