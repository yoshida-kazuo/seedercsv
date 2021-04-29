<?php

namespace Cerotechsys\Seedercsv\Services\Csv;

use SplFileObject;

class ParseService
{

    /**
     * Csv variable
     *
     * @var \SplFileObject
     */
    protected $Csv = null;

    /**
     * charset variable
     *
     * @var string
     */
    protected $charset = 'sjis-win';

    /**
     * Constructor
     *
     * @param string $filepath
     *
     * @return void
     */
    public function __construct(string $filepath)
    {
        $this->Csv = new SplFileObject($filepath);

        $this->charset = mb_detect_encoding(
            file_get_contents($filepath),
            'UTF-8',
            true
        ) === false ? $this->charset : 'utf-8';

        info(
            json_encode([
                'filepath'  => $filepath,
                'charset'   => $this->charset,
            ])
        );
    }

    /**
     * create function
     *
     * @return array
     */
    public function create() : array
    {
        $headers = [];
        $create = [];
        $cond = [];
        $countCols = 0;

        while(! $this->Csv->eof()) {

            $buff = $this->Csv->fgets();
            if ($this->Csv->key() === 0
                && substr($buff, 0, 3) === chr(hexdec('EF')).chr(hexdec('BB')).chr(hexdec('BF'))
            ) {
                $buff = substr($buff, 3);
            }

            $buff = explode(',', str_replace(["\r\n", "\n"], '', $buff));

            if ($this->charset === 'sjis-win') {
                mb_convert_encoding('utf-8', $this->charset, $buff);
            }

            $replace = function($value, string $enclosure = "/^\"|\"$/") {
                return preg_replace($enclosure, '', $value);
            };

            if ($this->Csv->key() === 0) {

                foreach ($buff as $key => $colname) {
                    $headers[$key] = $replace($colname);
                    $countCols++;
                }

            } elseif ($this->Csv->key() === 1) {

                foreach ($buff as $key => $colname) {
                    $cond[$key] = $replace($colname);
                }

            } elseif (count($buff) === $countCols) {

                foreach ($buff as $key => $value) {

                    if ($value === '""') {
                        $value = "";
                    } else {
                        $value = $replace($value);

                        if (! strlen($value)) {
                            $value = null;
                        }
                    }

                    $create['data'][$this->Csv->key()][$headers[$key]] = $value;

                    if (! empty($cond[$key])) {
                        $create['cond'][$this->Csv->key()][$cond[$key]] = $create['data'][$this->Csv->key()][$headers[$key]];
                    }

                }

            }

        }

        return $create;
    }

}
