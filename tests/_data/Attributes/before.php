<?php

namespace App\Demo;

use App\Attr;
use App\Attr\Covers;
use App\Attr\Other as Aliased;
use function App\Attr\helper;

#[\App\Attr\Covers('fully qualified')]
class FullyQualified
{
}

#[Covers('imported short name')]
class Imported
{
}

#[Attr\Covers('partially qualified through the imported first segment')]
class PartiallyQualified
{
}

#[Aliased('aliased import')]
class AliasedImport
{
}

#[covers('import lookup is case insensitive')]
class DifferentCase
{
}

#[Covers('grouped'), \App\Attr\Other('grouped')]
class GroupedResolvable
{
}

#[\App\Attr\Covers('grouped'), Missing('grouped')]
class GroupedWithUnimported
{
}

#[Missing('no import anywhere')]
class Unimported
{
}

#[Covers('a', 'b'), Missing('c', 'd')]
class ArgumentCommasAreNotSeparators
{
}

class Members
{
    #[Covers('resolvable')] #[Missing('unimported')]
    public function twoAttributesOnOneLine(): void
    {
    }

    #[helper('a function import does not resolve a class')]
    public function functionImport(): void
    {
    }
}
