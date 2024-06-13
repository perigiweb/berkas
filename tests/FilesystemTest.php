<?php

use PHPUnit\Framework\TestCase;
use Perigi\Berkas\Berkas;
use Perigi\Berkas\Exception\BerkasException;
use Perigi\Berkas\Exception\NotSupportedException;

class FilesystemTest extends TestCase
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

  public function testGetFiles()
  {
    $berkas = new Berkas('filesystem', $this->path);
    $files = $berkas->getFiles();

    $this->assertCount(3, $files);
  }

  public function testGetFilesOnEmptyFolder(){
    $berkas = new Berkas('filesystem', $this->path);

    $files = $berkas->getFiles('images');
    $this->assertCount(0, $files);
  }

  public function testGetFilesOnNotExistFolder(){
    $berkas = new Berkas('filesystem', $this->path);

    $this->expectException(BerkasException::class);
    $files = $berkas->getFiles('nonexist-folder');
  }

  public function testGetIncludeHiddenFiles(){
    $berkas = new Berkas('filesystem', $this->path);
    $berkas->getStorage()->setIncludeHiddenFile(true);
    $files = $berkas->getFiles();
    $this->assertCount(4, $files);
  }

  public function testEmptyBasePath()
  {
    $this->expectException(InvalidArgumentException::class);
    $berkas = new Berkas('filesystem', '');
  }

  public function testInvalidPath()
  {
    $this->expectException(BerkasException::class);
    $berkas = new Berkas('filesystem', 'path/invalid');
  }

  public function testNotSupportedStorage()
  {
    $this->expectException(NotSupportedException::class);
    $berkas = new Berkas('unknownstorage', []);
  }
}
