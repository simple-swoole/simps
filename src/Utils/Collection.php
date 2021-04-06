<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */
namespace Simps\Utils;

class Collection implements \ArrayAccess
{
    protected $data;

    public function __construct($data)
    {
        if ($data instanceof Collection) {
            $this->data = $data->get();
        } else {
            $this->data = $data;
        }
    }

    public function has($key)
    {
        // 判断如果有 .
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $id = &$this->data;
            foreach ($keys as $key) {
                if (isset($id[$key])) {
                    $id = &$id[$key];
                } else {
                    return false;
                }
            }
            return true;
        }
        return isset($this->data[$key]);
    }

    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->data;
        }
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $id = &$this->data;
            foreach ($keys as $key) {
                if (isset($id[$key])) {
                    $id = &$id[$key];
                } else {
                    return null;
                }
            }
            return $id;
        }
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return $default;
    }

    public function set($key, $value)
    {
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $id = &$this->data;
            foreach ($keys as $key) {
                if (isset($id[$key])) {
                    $id = &$id[$key];
                } else {
                    return;
                }
            }
            $id = $value;
        } else {
            $this->data[$key] = $value;
        }
    }

    public function first()
    {
        if (isset($this->data[0])) {
            return $this->data[0];
        }
        return null;
    }

    public function filter($call)
    {
        $result = [];
        foreach ($this->data as $k => $v) {
            if ($call($v, $k)) {
                $result[] = $v;
            }
        }
        return $result;
    }

    public function toArray()
    {
        return $this->data;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
