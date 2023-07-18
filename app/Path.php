<?php


namespace App;


class Path
{
    private string $_path;
    private const DS = DIRECTORY_SEPARATOR;

    public function __construct(string $path = '')
    {
        $ds = self::DS;
        # if first character of path is not a directory separator add it
        $path = !in_array(substr($path,0,1),['/','\\']) ? $ds.$path : $path;
        # replace wrongly formatted directory separators
        $this->_path = APPROOT.str_replace(['/','\\'],[$ds,$ds],$path);
    }

    public function __toString()
    {
        return $this->_path;
    }
}
