<?php
namespace Bricks\Storage\KeyValue;

/**
 * Хранилище типа ключ-значение.
 *
 * @author Artur Sh. Mamedbekov
 */
interface Storage extends \ArrayAccess{
  /**
   * Добавляет значение в хранилище.
   *
   * @param string $key Ключ, под которым сохраняется значение.
   * @param mixed $value Значение, сохраняемое в хранилище.
   * @param int $ttl [optional] Временная метка (time to live), после 
   * наступления которой значение будет считаться устаревшим.
   */
  public function set($key, $value, $ttl = 0);

  /**
   * Получает значение из хранилища.
   *
   * @param string $key Ключ целевого значения.
   *
   * @return mixed|null Запрашиваемое значение или null - если данный ключ не 
   * задан.
   */
  public function get($key);

  /**
    * Проверяет наличие ключа в хранилище.
    *
    * @param string $key Проверяемый ключ.
    *
    * @return bool false - если указанный ключ не задан в хранилище.
   */
  public function has($key);

  /**
   * Обновляет ttl ключа.
   *
   * @param string $key Обновляемый ключ.
   * @param int $ttl [optional] Временная метка (time to live), устанавливаемая 
   * ключу.
   */
  public function touch($key, $ttl = 0);

  /**
   * Удаляет ключ из хранилища.
   *
   * @param string $key Удаляемый ключ.
   */
  public function delete($key);
}
