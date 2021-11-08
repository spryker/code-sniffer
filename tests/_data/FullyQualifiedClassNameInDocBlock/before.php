<?php declare(strict_types = 1);

namespace Spryker;

use BarBaz;
use Custom\MyException;
use Some\MyClass;

/**
 * @method MyClass getFoo() Some desc
 * @method \App\Model\Entity\ReleaseGroup[]|\Datasource\ResultSetInterface|false deleteManyOrFail(iterable $entities, $options = [])
 */
class FixMe
{
    /**
     * @var MyClass Some comment
     */
    public $myProp;

    /**
     * @var BarBaz|string|null
     */
    public $myOtherProp;

    /**
     * @param \Some\MyClass|\Other\OtherClass|null $input
     * @return \Foo\Bar|\BarBaz
     */
    public function dontTouchMe($input = null)
    {
        return $input;
    }

    /**
     * @param MyClass|\Other\OtherClass|null $input
     * @throws MyException
     * @return \Foo\Bar|BarBaz
     */
    public function fixMe($input = null)
    {
        return $input;
    }

    /**
     * @param array<string, MyClass>|BarBaz|null $input X
     * @return (\Foo\Bar|BarBaz)[]
     */
    public function tooComplex($input = null)
    {
        return $input;
    }
}
