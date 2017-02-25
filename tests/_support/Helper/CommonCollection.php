<?php namespace Helper;

trait CommonCollection
{
    // contains regex

    public function assertContainsRegex($needle_regex, $haystack)
    {
        return array_reduce($haystack, function ($carry, $item) use ($needle_regex) {
            return preg_match($needle_regex, $item) === 1 ? true : $carry;
        }, false);
    }

    // not contains regex

    public function assertNotContainsRegex($needle_regex, $haystack)
    {
        return !$this->assertContainsRegex($needle_regex, $haystack);
    }
}
