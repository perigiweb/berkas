<?php

use PHPUnit\Framework\TestCase;
use Perigi\Berkas\Berkas;
use Perigi\Berkas\Exception\BerkasException;
use Perigi\Berkas\Exception\NotSupportedException;
use Perigi\Berkas\FileInfo;

class UploadFileTest extends TestCase
{
  protected $path;

  protected function setUp(): void
  {
    $this->path = __DIR__ . '/assets';
    if (!is_dir($this->path . '/images'))
      mkdir($this->path . '/images', 0777, true);

    if (!is_dir($this->path . '/videos'))
      mkdir($this->path . '/videos', 0777, true);

    if (!is_dir($this->path . '/files'))
      mkdir($this->path . '/files', 0777, true);
  }

  public function testCreateFromUploadedFiles()
  {
    $_FILES['test'] = [
      'tmp_name' => '/tmp/abcEddfdf',
      'name' => 'test.txt',
      'size' => 1234,
      'type' => 'text/plain',
      'error' => 0
    ];
    $_FILES['test_array'] = [
      'tmp_name' => [
        '/tmp/aasddd',
        '/tmp/asdnnde'
      ],
      'name' => [
        'image.jpg',
        'image.png'
      ],
      'size' => [
        1345,
        1245
      ],
      'type' => [
        'image/jpeg',
        'image/png'
      ],
      'error' => [
        0, 0
      ]
    ];
    $_FILES['test_array2'] = [
      'sub_array' => [
        'tmp_name' => [
          '/tmp/aasddd',
          '/tmp/asdnnde'
        ],
        'name' => [
          'image.jpg',
          'image.png'
        ],
        'size' => [
          1345,
          1245
        ],
        'type' => [
          'image/jpeg',
          'image/png'
        ],
        'error' => [
          0, 0
        ]
      ]
    ];

    $uploadedFiles = FileInfo::createFromUploadedFiles();
    $this->assertIsArray($uploadedFiles);

    //print_r($uploadedFiles);
  }

  public function testCreateFromUrl()
  {
    $uploadedFiles = FileInfo::createFromUrl('https://raw.githubusercontent.com/perigiweb/excel-to-pdf-web/main/README.md');

    $this->assertCount(1, $uploadedFiles);

    //print_r($uploadedFiles);
  }

  public function testUploadFromFileUploadNotUploadedFile()
  {
    $berkas = new Berkas('filesystem', $this->path);
    $tmp_file = tempnam(sys_get_temp_dir(), 'pb_');
    $fp = fopen($tmp_file, 'w');
    fwrite($fp, 'Test upload file.');
    fclose($fp);

    $_FILES = [
      'file' => [
        'tmp_name' => $tmp_file,
        'name' => 'test-file.txt',
        'error' => 0,
        'type' => 'text/plain'
      ]
    ];

    $validations = [
      'extension' => ['txt'],
      'size' => ['512K'],
      'mimetype' => ['text/plain']
    ];

    $this->expectException(BerkasException::class);
    $uploadResult = $berkas->fromFileUpload('files')->upload($validations, 'files');

    unlink($tmp_file);
  }

  public function testUploadFromUrl()
  {
    $berkas = new Berkas('filesystem', $this->path);

    $validations = [
      'extension' => ['png', 'jpg', 'jpeg'],
      'mimetype' => ['image/png', 'image/jpeg', 'image/pjpeg']
    ];

    $uploadResult = $berkas->fromUrl('https://www.php.net/favicon-196x196.png?v=2')
      ->upload($validations, 'images', true);
    $this->assertTrue($uploadResult);

    $uploadedFiles = $berkas->getUploadedFiles();
    $this->assertCount(1, $uploadedFiles);
  }
}
