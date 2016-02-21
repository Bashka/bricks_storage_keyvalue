<?php
namespace Bricks\Storage\KeyValue;

/**
 * Хранилище типа ключ-значение на основе файловой системы.
 *
 * @author Artur Sh. Mamedbekov
 */
class FileStorage extends AbstractStorage{
  /**
   * @var string Адрес каталога, используемого в качестве хранилища.
   */
  private $store;

  /**
   * Формирует адрес файла, хранящего значение с данным хешем.
   *
   * @param string $hash Целевой хэш.
   *
   * @return string Адрес файла, хранящего значение данного хеша.
   */
  private function fileAddress($hash){
    $levelA = $this->store . DIRECTORY_SEPARATOR . substr($hash, 0, 2);
    $levelB = $levelA . DIRECTORY_SEPARATOR . substr($hash, 2, 2);
    if(!file_exists($levelB)){
      mkdir($levelB, 0755, true);
    }

    return $levelB . DIRECTORY_SEPARATOR . $hash;
  }

  /**
   * @param string $store Адрес каталога, используемого в качестве хранилища.
   */
  public function __construct($store){
    $this->store = $store;
  }

  /**
   * @see Storage::set
   */
  public function set($key, $value, $ttl = 0){
    $pblock = $this->fileAddress(md5($key));
    $pmeta = $pblock . '_meta';

    if(!file_exists($pmeta)){
      touch($pmeta);
    }

    $rmeta = fopen($pmeta, 'r+');
    flock($rmeta, LOCK_EX);
    $smeta = filesize($pmeta);
    $meta = $smeta? unserialize(fread($rmeta, $smeta)) : [];
    if(isset($meta['ttl']) && $meta['ttl'] != 0 && $meta['ttl'] < time()){
      $meta = [];
    }
    fseek($rmeta, 0);
    ftruncate($rmeta, 0);
    $rblock = fopen($pblock, 'w');

    if($meta && isset($meta['serialize']) && $meta['serialize'] == false){
      fwrite($rblock, (string) $value);
    }
    else{
      fwrite($rblock, serialize($value));
    }
    fwrite($rmeta, serialize(array_replace($meta, [
      'ttl' => (int) $ttl,
    ])));

    fflush($rblock);
    fflush($rmeta);
    clearstatcache(true, $pblock);
    clearstatcache(true, $pmeta);
    fclose($rblock);
    fclose($rmeta);
  }

  /**
   * @see Storage::get
   */
  public function get($key){
    $pblock = $this->fileAddress(md5($key));
    $pmeta = $pblock . '_meta';

    if(!file_exists($pmeta)){
      return null;
    }

    $rmeta = fopen($pmeta, 'r');
    flock($rmeta, LOCK_SH);
    $meta = unserialize(fread($rmeta, filesize($pmeta)));
    if($meta['ttl'] != 0 && $meta['ttl'] < time()){
      fclose($rmeta);
      return null;
    }

    $rblock = fopen($pblock, 'r');
    if($meta && isset($meta['serialize']) && $meta['serialize'] == false){
      $value = fread($rblock, filesize($pblock));
    }
    else{
      $value = unserialize(fread($rblock, filesize($pblock)));
    }

    fclose($rblock);
    fclose($rmeta);

    return $value;
  }

  /**
   * @see Storage::has
   */
  public function has($key){
    $pmeta = $this->fileAddress(md5($key)) . '_meta';

    if(!file_exists($pmeta)){
      return false;
    }

    $rmeta = fopen($pmeta, 'r');
    flock($rmeta, LOCK_SH);
    $meta = unserialize(fread($rmeta, filesize($pmeta)));
    fclose($rmeta);

    return $meta['ttl'] >= time() || $meta['ttl'] == 0;
  }

  /**
   * @see Storage::touch
   */
  public function touch($key, $ttl = 0){
    $pmeta = $this->fileAddress(md5($key)) . '_meta';

    if(!file_exists($pmeta)){
      return;
    }

    $rmeta = fopen($pmeta, 'r+');
    flock($rmeta, LOCK_EX);
    ftruncate($rmeta, 0);
    fwrite($rmeta, serialize([
      'ttl' => (int) $ttl,
    ]));

    fflush($rmeta);
    clearstatcache(true, $pmeta);
    fclose($rmeta);
  }

  /**
   * @see Storage::delete
   */
  public function delete($key){
    $pblock = $this->fileAddress(md5($key));
    $pmeta = $pblock . '_meta';

    if(!file_exists($pmeta)){
      return;
    }

    $rmeta = fopen($pmeta, 'r+');
    flock($rmeta, LOCK_EX);
    unlink($pblock);
    unlink($pmeta);

    fclose($rmeta);
  }

  /**
   * Получает или устанавливает метаданные ключа.
   * Метаданные могут быть установлены даже если целевой ключ не используется в 
   * хранилище. В этом случае ключ будет создан со значением null.
   * В качестве метаданных могут использоваться любые наименования, но следующие 
   * имеют особое значение:
   *   - ttl - временная метка (time to live) ключа
   *   - serialize - используется ли PHP сериализация при записи и получении 
   *   значения ключа (по умолчанию - используется)
   *
   * @param string $key Целевой ключ.
   * @param string $name Наименование метаданных.
   * @param mixed $value [optional] Значение.
   *
   * @return mixed Значение метаданных. Будет возвращено только если последний 
   * параметр не передан.
   */
  public function meta($key, $name, $value = null){
    $pmeta = $this->fileAddress(md5($key)) . '_meta';

    if(!file_exists($pmeta)){
      $this->set($key, null);
    }

    $rmeta = fopen($pmeta, 'r+');
    if(is_null($value)){
      flock($rmeta, LOCK_SH);
      $meta = unserialize(fread($rmeta, filesize($pmeta)));
      $result = isset($meta[$name])? $meta[$name] : null;
      fclose($rmeta);

      return $result;
    }
    else{
      flock($rmeta, LOCK_EX);
      $meta = unserialize(fread($rmeta, filesize($pmeta)));
      fseek($rmeta, 0);
      $meta[$name] = $value;
      ftruncate($rmeta, 0);
      fwrite($rmeta, serialize($meta));
      fflush($rmeta);
      clearstatcache(true, $pmeta);
      fclose($rmeta);
    }
  }
}
