<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ElectreController extends Controller
{
    public function index()
    {
        $value = [
            [500, 4, 2, 4, 3],
            [600, 4, 4, 3, 3],
            [600, 3, 3, 3, 3],
            [800, 5, 5, 4, 4],
            [400, 3, 1, 2, 4],
        ];
        $weight = [4, 4, 4, 3, 2];

        $electre = $this->electre($value, $weight);

        dd($electre);

//        dd(count($value));

//        dd($himpunan);

        return view('electre.index');
    }

    public function electre($value, $weight)
    {
        $normalize = $this->normalize($value);

        $pembobotan = $this->pembobotan($normalize, $weight);

        $himpunan = $this->himpunan($pembobotan, $weight);

        $concordance = $this->concordance($himpunan['concordance'], count($value));

        $discordance = $this->discordance($himpunan['discordance'], count($value), $himpunan['selisih']);

        $threshold_concordance = $this->threshold($concordance, count($value));

        $threshold_discordance = $this->threshold($discordance, count($value));

        $dominance_concordance = $this->dominance_matrix($concordance, $threshold_concordance);

        $dominance_discordance = $this->dominance_matrix($discordance, $threshold_discordance);

        $agregate_domination = $this->agregate_dominace($dominance_concordance, $dominance_discordance);

        $rangking = $this->ranking($concordance, $discordance);


        return[
            'normalize' => $normalize,
            'pembobotan' => $pembobotan,
            'himpunan' => $himpunan,
            'concordance' => $concordance,
            'discordance' => $discordance,
            'threshold_concordance' => $threshold_concordance,
            'threshold_discordance' => $threshold_discordance,
            'dominance_concordance' => $dominance_concordance,
            'dominance_discordance' => $dominance_discordance,
            'agregate_domination' => $agregate_domination,
            'rangking' => $rangking,
        ];

    }

    public function normalize($value) : array
    {
        $x = [];

        for ($j = 0; $j < count($value[0]); $j++) {
            $transposedData = [];

            for ($i = 0; $i < count($value); $i++) {
                $transposedData[$i] = $value[$i][$j];
            }

            $mmultResult = 0;
            for ($i = 0; $i < count($value); $i++) {
                $mmultResult += $value[$i][$j] * $transposedData[$i];
            }

            $x[$j] = sqrt($mmultResult);
        }

        $normalize = [];
        for ($i = 0; $i < count($value[0]); $i++) {
            for ($j = 0; $j < count($value); $j++) {
                $normalize[$j][$i] = $value[$j][$i] / $x[$i];
            }
        }
        return $normalize;
    }

    public function pembobotan($value, $weight) : array
    {
        $pembobotan = [];
        for ($i = 0; $i < count($value); $i++) {
            for ($j = 0; $j < count($value[0]); $j++) {
                $pembobotan[$i][$j] = $value[$i][$j] * $weight[$j];
            }
        }
        return $pembobotan;
    }

    public function himpunan($value,$weight) : array
    {
        $himpunan_concordance = [];
        $himpunan_discordance = [];
        $seluruh_selisih = [];
        $length = 0;

        for ($i=0; $i < count($value); $i++) {
            for ($j=0; $j < count($value); $j++) {
                if ($i != $j) {
                    for ($k=0; $k < count($value[0]); $k++) {
                        if ($value[$i][$k] >= $value[$j][$k]) {
                            $himpunan_concordance[$length][$k] = $weight[$k];
                            $himpunan_discordance[$length][$k] = 0;
                        } else {
                            $himpunan_discordance[$length][$k] = $value[$i][$k] - $value[$j][$k];
                            if ($himpunan_discordance[$length][$k] < 0) {
                                $himpunan_discordance[$length][$k] = $himpunan_discordance[$length][$k] * -1;
                            }
                            $himpunan_concordance[$length][$k] = 0;
                        }
                        $seluruh_selisih[$length][$k] = $value[$i][$k] - $value[$j][$k];
                        if ($seluruh_selisih[$length][$k] < 0) {
                            $seluruh_selisih[$length][$k] = $seluruh_selisih[$length][$k] * -1;
                        }
                    }
                    $length++;
                }
            }
        }

        return [
            'concordance' => $himpunan_concordance,
            'discordance' => $himpunan_discordance,
            'selisih' => $seluruh_selisih,
        ];
    }

    public function concordance($value, $array_length) : array
    {
        $concordance = [];
        $length = 0;
       for ($i=0; $i < $array_length; $i++) {
           for ($j=0; $j < $array_length; $j++) {
               $concordance[$i][$j] = 0;
               if ($i != $j) {
                   for ($k=0; $k < count($value[0]); $k++) {
                       $concordance[$i][$j] += $value[$length][$k];
                   }
                   $length++;
               }
           }
       }

         return $concordance;
    }

    public function discordance($value, $array_length, $seluruh_selisih) : array
    {
        $discordance = [];
        $length = 0;
        for ($i=0; $i < $array_length; $i++) {
            for ($j=0; $j < $array_length; $j++) {
                $discordance[$i][$j] = 0;
                if ($i != $j) {
                    for ($k=0; $k < count($value[0]); $k++) {
                        $discordance[$i][$j] = max($value[$length])/max($seluruh_selisih[$length]);
                    }
                    $length++;
                }

            }
        }


        return $discordance;
    }

    public function threshold($value, $array_length)
    {
        $sum = 0;
        for ($i=0; $i < count($value); $i++) {
            for ($j=0; $j < count($value[0]); $j++) {
                $sum += $value[$i][$j];
            }
        }
        $threshold = $sum / ($array_length * ($array_length - 1));

        return $threshold;
    }

    public function dominance_matrix($value, $threshold)
    {
        $dominance_matrix = [];
        for ($i=0; $i < count($value); $i++) {
            for ($j=0; $j < count($value[0]); $j++) {
                if ($value[$i][$j] >= $threshold) {
                    $dominance_matrix[$i][$j] = 1;
                } else {
                    $dominance_matrix[$i][$j] = 0;
                }
            }
        }
        return $dominance_matrix;
    }

    public function agregate_dominace($f,$g)
    {
        $agregate_dominace = [];
        for ($i=0; $i < count($f); $i++) {
            for ($j=0; $j < count($f[0]); $j++) {
                $agregate_dominace[$i][$j] = 0;
                if ($f[$i][$j] == 1 && $g[$i][$j] == 1) {
                    $agregate_dominace[$i][$j] = 1;
                }
            }
        }
        return $agregate_dominace;
    }

    public function ranking($discordance, $concordance)
    {
        $ranking = [];
        for ($i=0; $i < count($discordance); $i++) {
            $ranking[$i] = 0;
            for ($j=0; $j < count($discordance[0]); $j++) {
                $ranking[$i] += $discordance[$i][$j] - $concordance[$i][$j];
            }
        }
        return $ranking;
    }
}
