<?php

function getTextDiff($textA, $textB, $delimeter = "\n")
{
    if (!is_string($textA) || !is_string($textB) || !is_string($delimeter)) {
        return FALSE;
    }

    // Получение уникальных слов(строк)
    $arrA = explode($delimeter, str_replace("\r", "", $textA));
    $arrB = explode($delimeter, str_replace("\r", "", $textB));
    $unickTable = array_unique(array_merge($arrA, $arrB));
    $unickTableFlip = array_flip($unickTable);

    // Приводим к тексту из идентификаторов
    $arrAid = $arrBid = [];
    foreach ($arrA as $v) {
        $arrAid[] = $unickTableFlip[$v];
    }
    foreach ($arrB as $v) {
        $arrBid[] = $unickTableFlip[$v];
    }

    // Выбор наибольшей общей последовательности
    $maxLen = [];
    for ($i = 0, $x = count($arrAid); $i <= $x; $i++) {
        $maxLen[$i] = [];
        for ($j = 0, $y = count($arrBid); $j <= $y; $j++) {
            $maxLen[$i][$j] = '';
        }
    }
    for ($i = count($arrAid) - 1; $i >= 0; $i--) {
        for ($j = count($arrBid) - 1; $j >= 0; $j--) {
            if ($arrAid[$i] == $arrBid[$j]) {
                @$maxLen[$i][$j] = 1 + $maxLen[$i + 1][$j + 1];
            } else {
                $maxLen[$i][$j] = max($maxLen[$i + 1][$j], $maxLen[$i][$j + 1]);
            }
        }
    }
    $longest = [];
    for ($i = 0, $j = 0; $maxLen[$i][$j] != 0 && $i < $x && $j < $y;) {
        if ($arrAid[$i] == $arrBid[$j]) {
            $longest[] = $arrAid[$i];
            $i++;
            $j++;
        } else {
            if ($maxLen[$i][$j] == $maxLen[$i + 1][$j]) {
                $i++;
            } else {
                $j++;
            }
        }
    }

    // Сравниваем строки, ищем изменения
    $arrBidDiff = [];
    $i1 = 0;
    $i2 = 0;
    for ($i = 0, $iters = count($arrBid); $i < $iters; $i++) {
        $simbol = [];
        if (isset($longest[$i1]) && $longest[$i1] == $arrBid[$i2]) {
            $simbol[] = $longest[$i1];
            $simbol[] = "*";
            $arrBidDiff[] = $simbol;
            $i1++;
            $i2++;
        } else {
            $simbol[] = $arrBid[$i2];
            $simbol[] = "+";
            $arrBidDiff[] = $simbol;
            $i2++;
        }
    }
    $arrAidDiff = [];
    $i1 = 0;
    $i2 = 0;
    for ($i = 0, $iters = count($arrAid); $i < $iters; $i++) {
        $simbol = [];
        if (isset($longest[$i1]) && $longest[$i1] == $arrAid[$i2]) {
            $simbol[] = $longest[$i1];
            $simbol[] = "*";
            $arrAidDiff[] = $simbol;
            $i1++;
            $i2++;
        } else {
            $simbol[] = $arrAid[$i2];
            $simbol[] = "-";
            $arrAidDiff[] = $simbol;
            $i2++;
        }
    }

    // Меняем идентификаторы обратно на текст
    $arrAdiff = [];
    foreach ($arrAidDiff as $v) {
        $arrAdiff[] = array(
            $unickTable[$v[0]],
            $v[1],
        );
    }
    $arrBdiff = [];
    foreach ($arrBidDiff as $v) {
        $arrBdiff[] = array(
            $unickTable[$v[0]],
            $v[1],
        );
    }

    // Если на одной и той же позиции у текста A "добавлено" а у B "удалено" - меняем метку на "изменено"
    $max = max(count($arrAdiff), count($arrBdiff));
    for ($i1 = 0, $i2 = 0; $i1 < $max && $i2 < $max;) {
        if (!isset($arrAdiff[$i1]) || !isset($arrBdiff[$i2])) {
            // no action
        } elseif ($arrAdiff[$i1][1] == "-" && $arrBdiff[$i2][1] == "+" && $arrBdiff[$i2][0] != "") {
            $arrAdiff[$i1][1] = "*";
            $arrBdiff[$i2][1] = "m";
        } elseif ($arrAdiff[$i1][1] != "-" && $arrBdiff[$i2][1] == "+") {
            $i2++;
        } elseif ($arrAdiff[$i1][1] == "-" && $arrBdiff[$i2][1] != "+") {
            $i1++;
        }
        $i1++;
        $i2++;
    }

    // Оборачиваем изменения в теги для последующей стилизации
    $textA = [];
    foreach ($arrAdiff as $v) {
        if ('+' == $v[1]) {
            $textA[] = '<span class="diff-added">' . $v[0] . '</span>';
        } elseif ('-' == $v[1]) {
            $textA[] = '<span class="diff-deleted">' . $v[0] . '</span>';
        } elseif ('m' == $v[1]) {
            $textA[] = '<span class="diff-changed">' . $v[0] . '</span>';
        } else {
            $textA[] = '<span class="diff-match">' . $v[0] . '</span>';
        }
    }
    $textA = implode($delimeter, $textA);
    $textB = [];
    foreach ($arrBdiff as $v) {
        if ('+' == $v[1]) {
            $textB[] = '<span class="diff-added">' . $delimeter . $v[0] . '</span>';
        } elseif ('-' == $v[1]) {
            $textB[] = '<span class="diff-deleted">' . $delimeter . $v[0] . '</span>';
        } elseif ('m' == $v[1]) {
            $textB[] = '<span class="diff-changed">' . $delimeter . $v[0] . '</span>';
        } else {
            $textB[] = '<span class="diff-match">' . $delimeter . $v[0] . '</span>';
        }
    }
    $textB = implode('', $textB);

    return array($textA, $textB);
}

if (isset($_POST['textA']) && isset($_POST['textB'])) {
    echo json_encode([
        'text' => getTextDiff($_POST['textA'], $_POST['textB'], " "),
    ]);
} else {
    echo json_encode([
        'error' => 'Один из обязательных параметров (textA и textB) не передан.',
    ]);
}
