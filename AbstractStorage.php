<?php
namespace Bricks\Storage\KeyValue;

abstract class AbstractStorage implements Storage{
  public function offsetExists($offset){
    return $this->has($offset);
  }

  public function offsetGet($offset){
    return $this->get($offset);
  }

  public function offsetSet($offset, $value){
    $this->set($offset, $value);
  }

  public function offsetUnset($offset){
    $this->delete($offset);
  }
}
