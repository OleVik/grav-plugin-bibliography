<?php

/*
 * Copyright (C) 2016 Sebastian Böttger <seboettg@gmail.com>
 * You may use, distribute and modify this code under the
 * terms of the MIT license.
 *
 * You should have received a copy of the MIT license with
 * this file. If not, please visit: https://opensource.org/licenses/mit-license.php
 */

namespace Seboettg\Collection\Test;

use PHPUnit\Framework\TestCase;
use Seboettg\Collection\ArrayList;
use Seboettg\Collection\Comparable;

class ArrayListTest extends TestCase
{

    /**
     * @var ArrayList
     */
    private $numeratedArrayList;

    /**
     * @var ArrayList
     */
    private $hashMap;


    public function setUp()
    {
        $this->numeratedArrayList = new ArrayList([
            new Element("a", "aa"),
            new Element("b", "bb"),
            new Element("c", "cc"),
            new Element("k", "kk"),
            new Element("d", "dd"),
        ]);

        $this->hashMap = new ArrayList([
            "c" => new Element("c"),
            "a" => new Element("a"),
            "h" => new Element("h"),
        ]);
    }

    public function testCurrent()
    {
        $this->assertTrue($this->numeratedArrayList->current()->getAttr2() === "aa");
        $arrayList = new ArrayList();
        $this->assertFalse($arrayList->current());
    }

    public function testNext()
    {
        $this->assertTrue($this->numeratedArrayList->next()->getAttr2() === "bb");
    }

    public function testPrev()
    {
        $this->numeratedArrayList->next();
        $this->assertEquals($this->numeratedArrayList->prev()->getAttr2(), "aa");
        $this->assertFalse($this->numeratedArrayList->prev());
    }

    public function testAppend()
    {
        $i = $this->numeratedArrayList->count();
        $this->numeratedArrayList->append(new Element("3", "33"));
        $j = $this->numeratedArrayList->count();
        $this->assertEquals($i + 1, $j);
        $this->assertEquals("3", $this->numeratedArrayList->toArray()[$i]->getAttr1());
    }

    public function testSet()
    {
        $this->hashMap->set("c", new Element("ce"));
        $this->assertEquals("ce", $this->hashMap->toArray()['c']->getAttr1());
    }

    public function testCompareTo()
    {
        $arr = $this->hashMap->toArray();
        usort($arr, function (Comparable $a, Comparable $b) {
            return $a->compareTo($b);
        });

        $this->assertEquals("a", $arr[0]->getAttr1());
        $this->assertEquals("c", $arr[1]->getAttr1());
        $this->assertEquals("h", $arr[2]->getAttr1());
    }

    public function testReplace()
    {
        $this->hashMap->replace($this->numeratedArrayList->toArray());
        $keys = array_keys($this->hashMap->toArray());
        foreach ($keys as $key) {
            $this->assertInternalType("int", $key);
            $this->assertNotEmpty($this->hashMap->get($key));
        }
    }

    public function testClear()
    {
        $this->assertTrue($this->hashMap->count() > 0);
        $this->assertEquals(0, $this->hashMap->clear()->count());
    }

    public function testSetArray()
    {
        $this->hashMap->setArray($this->numeratedArrayList->toArray());
        $keys = array_keys($this->hashMap->toArray());
        foreach ($keys as $key) {
            $this->assertInternalType("int", $key);
            $this->assertNotEmpty($this->hashMap->get($key));
        }
    }

    public function testShuffle()
    {
        $arr = $this->numeratedArrayList->toArray();
        usort($arr, function (Comparable $a, Comparable $b) {
            return $a->compareTo($b);
        });
        $this->numeratedArrayList->replace($arr);
        for ($i = 0; $i < $this->numeratedArrayList->count() - 1; ++$i) {
            $lte = ($this->numeratedArrayList->get($i)->getAttr1() <= $this->numeratedArrayList->get($i + 1)->getAttr1());
            if (!$lte) {
                break;
            }
        }
        //each element on position $i is smaller than or equal to the element on position $i+1
        $this->assertTrue($lte);
        $arr1 = $this->numeratedArrayList->toArray();

        $this->numeratedArrayList->shuffle();

        $arr2 = $this->numeratedArrayList->toArray();

        // at least one element has another position as before
        for ($i = 0; $i < count($arr); ++$i) {
            $equal = ($arr1[$i]->getAttr1() == $arr2[$i]->getAttr1());
            if (!$equal) {
                break;
            }
        }
        $this->assertFalse($equal);
    }


    public function testHasKey()
    {
        $this->assertTrue($this->numeratedArrayList->hasKey(0));
        $this->assertTrue($this->hashMap->hasKey("c"));
    }

    public function testHasValue()
    {
        $list = new ArrayList([
            "a",
            "b",
            "c"
        ]);

        $this->assertTrue($list->hasValue("a"));
    }

    public function testGetIterator()
    {
        $it = $this->numeratedArrayList->getIterator();

        foreach ($it as $key => $e) {
            $this->assertTrue(is_int($key));
            $this->assertInstanceOf("Seboettg\\Collection\\Test\\Element", $e);
        }
    }

    public function testRemove()
    {
        $list = new ArrayList([
            "a",
            "b",
            "c"
        ]);

        $list->append("d");
        $this->assertTrue($list->hasValue("d"));
        $list->remove(0);
        $this->assertFalse($list->hasValue("a"));
    }

    public function testOffsetGet()
    {
        $this->assertNotEmpty($this->numeratedArrayList[0]);
        $this->assertEmpty($this->numeratedArrayList[333]);
    }

    public function testOffsetSet()
    {
        $pos = $this->numeratedArrayList->count();
        $this->numeratedArrayList[$pos] = new Element($pos, $pos . $pos);
        $arr = $this->numeratedArrayList->toArray();
        $this->assertNotEmpty($arr[$pos]);
        $this->assertEquals($pos, $arr[$pos]->getAttr1());
    }

    public function testOffestExist()
    {
        $this->assertTrue(isset($this->hashMap['a']));
        $this->assertFalse(isset($this->numeratedArrayList[111]));
    }

    public function testOffsetUnset()
    {
        $list = new ArrayList(['a' => 'aa', 'b' => 'bb']);
        unset($list['a']);
        $this->assertFalse($list->hasKey('a'));
        $this->assertTrue($list->hasKey('b'));
    }

    public function testAdd()
    {
        $list = new ArrayList(['a' => 'aa', 'b' => 'bb', 'c' => 'cc']);
        $list->add('d', 'dd');
        $this->assertEquals('dd', $list->get('d'));
        $list->add('d', 'ddd');

        $dl = $list->get('d');
        $this->assertTrue(is_array($dl));
        $this->assertEquals('dd', $dl[0]);
        $this->assertEquals('ddd', $dl[1]);
    }

    public function testFilter()
    {
        // filter elements that containing values with attr1 'c' or 'h'
        $arrayList = $this->hashMap->filter(function (Element $elem) {
            return $elem->getAttr1() === 'c' || $elem->getAttr1() === 'h';
        });

        $this->assertTrue($arrayList->hasKey('c'));
        $this->assertTrue($arrayList->hasKey('h'));
        $this->assertFalse($arrayList->hasKey('a'));
        $this->assertEquals($arrayList->get('c')->getAttr1(), 'c');
        $this->assertEquals($arrayList->get('h')->getAttr1(), 'h');
    }

    public function testFilterByKeys()
    {
        $arrayList = $this->numeratedArrayList->filterByKeys([0, 3]);
        $this->assertFalse($arrayList->hasKey(1));
        $this->assertEquals($arrayList->count(), 2);
        $this->assertEquals($arrayList->current()->getAttr1(), "a");
        $this->assertEquals($arrayList->next()->getAttr1(), "k");
    }
}

class Element implements Comparable
{

    private $attr1;

    private $attr2;

    public function __construct($attr1, $attr2 = "")
    {
        $this->attr1 = $attr1;
        $this->attr2 = $attr2;
    }

    /**
     * @return mixed
     */
    public function getAttr1()
    {
        return $this->attr1;
    }

    /**
     * @param mixed $attr1
     */
    public function setAttr1($attr1)
    {
        $this->attr1 = $attr1;
    }

    /**
     * @return mixed
     */
    public function getAttr2()
    {
        return $this->attr2;
    }

    /**
     * @param mixed $attr2
     */
    public function setAttr2($attr2)
    {
        $this->attr2 = $attr2;
    }

    /**
     * Compares this object with the specified object for order. Returns a negative integer, zero, or a positive
     * integer as this object is less than, equal to, or greater than the specified object.
     *
     * The implementor must ensure sgn(x.compareTo(y)) == -sgn(y.compareTo(x)) for all x and y.
     *
     * @param Comparable $b
     * @return int
     */
    public function compareTo(Comparable $b)
    {
        return strcmp($this->attr1, $b->getAttr1());
    }
}