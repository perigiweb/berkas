# Berkas

PHP file manager (list and uploading files). Currently only support Filesystem storage, S3 compatible storage soon.

## How to Install

Install via composer

```bash
composer require perigiweb/berkas
```

## Usage

```php
<?php

use Perigi\Berkas\Berkas;

$berkas = new Berkas('filesystem', dirname(__DIR__).'/assets');

// or

use Perigi\Berkas\Berkas;
use Perigi\Berkas\Storage\Filesystem;

$storage = new Filesystem(dirname(__DIR__).'/assets');
$berkas = new Berkas($storage);

// List files
$files = $berkas->getFiles();

// List file in sub directory
$files = $berkas->getFiles('sub-dirs');

// Upload files from uploaded files
$validations = [
  'extension' => ['txt'],
  'size' => ['512K'],
  'mimetype' => ['text/plain']
];
$result = $berkas->fromFileUpload('file')->upload($validations, 'files');
if ($result){
  $uploadedFiles = $berkas->getUploadedFiles();
} else {
  $errors = $berkas->getErrors();
}

// Upload files from url
$validations = [
  'extension' => ['png', 'jpg', 'jpeg'],
  'mimetype' => ['image/png', 'image/jpeg', 'image/pjpeg']
];
$result = $berkas->fromUrl('https://example.com/files/filename.png')->upload($validations, 'images');

if ($result){
  $uploadedFiles = $berkas->getUploadedFiles();
} else {
  $errors = $berkas->getErrors();
}

```

## Author

[Perigi Web](https://github.com/perigiweb)


## License

MIT Public License
